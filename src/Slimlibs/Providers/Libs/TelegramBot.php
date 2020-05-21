<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Providers\Libs;

use Psr\Container\ContainerInterface;
use Albatiqy\Slimlibs\Support\Helper\CodeOut;

final class TelegramBot {

    public const STATE_FINISHED = 1;
    public const STATE_ERROR = 2;

    private $container;
    private $token;
    private $channelName;
    private $settings;
    private $users;
    private $db;

    public function __construct(ContainerInterface $container) {
        $settings = $container->get('settings');
        $this->db = $container->get('db')(); // pindahkan ke yang lain
        $fcache = $settings['cache']['base_dir'] . "/telegram-users.php";
        if (\file_exists($fcache)) {
            $expires = (\filemtime($fcache) + (60*60*5));
            $now = \time() + 30;
            if ($expires <= $now) {
                $this->users = $this->createUsersCache($fcache);
            } else {
                $this->users = require $fcache;
            }
        } else {
            $this->users = $this->createUsersCache($fcache);
        }
        $this->token = $settings['telegram_bot']['token'];
        $this->channelName = $settings['telegram_bot']['channelName'];
        $this->settings = $settings;
        $this->container = $container;
    }

    public function listen() {
        $console = \PHP_SAPI == 'cli' ? true : false;
        if (!$console) {
            throw new \Exception('listen harus dijalankan dalam mode cli');
        }
        try {
            $return = null;
            $queue = $this->getChannelQueue();
            foreach ($queue as $row) {
                switch ($row->type) {
                    case 0:
                        $return = $this->sendChannelText($row->text);
                    break;
                    case 1:
                        $return = $this->sendChannelPhoto($row->file, $row->caption);
                    break;
                }
                if (\is_object($return)) {
                    if ($return->ok) {
                        $this->setChannelMessageResult($row->id, $return->result->message_id, $return->result->date);
                    } else {
                        $this->setChannelMessageError($row->id, $return->description);
                    }
                }
            }
            $queue = $this->getUserQueue();
            foreach ($queue as $row) {
                switch ($row->type) {
                    case 0:
                        $return = $this->sendUserText($row->chat_id, $row->text);
                    break;
                    case 1:
                        $return = $this->sendUserPhoto($row->chat_id, $row->file, $row->caption);
                    break;
                }
                if (\is_object($return)) {
                    if ($return->ok) {
                        $this->setUserMessageResult($row->id, $return->result->message_id, $return->result->date);
                    } else {
                        $this->setUserMessageError($row->id, $return->description);
                    }
                }
            }
            $this->processUpdates();
        } catch (\Exception $e) {
            $this->log($e->getMessage());
        }
    }

    public function sendChannelText($text) {
        if (!$this->token) {
            return null;
        }
        $url = 'https://api.telegram.org/bot'.$this->token.'/sendMessage';
        $query = [
            'chat_id' => '@'.$this->channelName,
            'text' => $text
        ];
        $body = \http_build_query($query);

        $ch = \curl_init();
        \curl_setopt($ch, \CURLOPT_URL, $url);
        \curl_setopt($ch, \CURLOPT_POST, 1);
        \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, 1);
        \curl_setopt($ch, \CURLOPT_POSTFIELDS, $body);
        \curl_setopt($ch, \CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Content-Length: ' . \strlen($body),
            'Accept: application/json'
        ]);
        $result = \curl_exec($ch);
        if (\curl_errno($ch)) {
            $error_msg = \curl_error($ch);
            throw new \Exception($error_msg);
        }
        \curl_close($ch);
        $result = \json_decode($result);
        if ($result === null && \json_last_error() !== \JSON_ERROR_NONE) {
            throw new \Exception('Malformed JSON Response: '.\json_last_error_msg());
        }
        return $result;
    }

    public function sendChannelPhoto($file, $caption) {
        if (!$this->token) {
            return null;
        }
        $url = 'https://api.telegram.org/bot'.$this->token.'/sendPhoto';
        $body = [
            'chat_id' => '@'.$this->channelName,
            'photo' => new \CURLFile(\realpath($file)),
            'caption' => $caption
        ];

        $ch = \curl_init();
        \curl_setopt($ch, \CURLOPT_URL, $url);
        \curl_setopt($ch, \CURLOPT_POST, 1);
        \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, 1);
        \curl_setopt($ch, \CURLOPT_POSTFIELDS, $body);
        \curl_setopt($ch, \CURLOPT_HTTPHEADER, [
            'Content-Type: multipart/form-data',
            'Accept: application/json'
        ]);
        $result = \curl_exec($ch);
        if (\curl_errno($ch)) {
            $error_msg = \curl_error($ch);
            throw new \Exception($error_msg);
        }
        \curl_close($ch);
        $result = \json_decode($result);
        if ($result === null && \json_last_error() !== \JSON_ERROR_NONE) {
            throw new \Exception('Malformed JSON Response: '.\json_last_error_msg());
        }
        return $result;
    }

    public function sendUserText($chat_id, $text, $msg_id=null) {
        if (!$this->token) {
            return null;
        }
        $url = 'https://api.telegram.org/bot'.$this->token.'/sendMessage';
        $query = [
            'chat_id' => $chat_id,
            'text' => $text
        ];
        if ($msg_id!=null) {
            $query['reply_to_message_id'] = $msg_id;
        }
        $body = \http_build_query($query);

        $ch = \curl_init();
        \curl_setopt($ch, \CURLOPT_URL, $url);
        \curl_setopt($ch, \CURLOPT_POST, 1);
        \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, 1);
        \curl_setopt($ch, \CURLOPT_POSTFIELDS, $body);
        \curl_setopt($ch, \CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Content-Length: ' . \strlen($body),
            'Accept: application/json'
        ]);
        $result = \curl_exec($ch);
        if (\curl_errno($ch)) {
            $error_msg = \curl_error($ch);
            throw new \Exception($error_msg);
        }
        \curl_close($ch);
        $result = \json_decode($result);
        if ($result === null && \json_last_error() !== \JSON_ERROR_NONE) {
            throw new \Exception('Malformed JSON Response: '.\json_last_error_msg());
        }
        return $result;
    }

    public function sendUserPhoto($chat_id, $file, $caption, $msg_id=null) {
        if (!$this->token) {
            return null;
        }
        $url = 'https://api.telegram.org/bot'.$this->token.'/sendPhoto';
        $body = [
            'chat_id' => $chat_id,
            'photo' => new \CURLFile(\realpath($file)),
            'caption' => $caption
        ];
        if ($msg_id!=null) {
            $body['reply_to_message_id'] = $msg_id;
        }

        $ch = \curl_init();
        \curl_setopt($ch, \CURLOPT_URL, $url);
        \curl_setopt($ch, \CURLOPT_POST, 1);
        \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, 1);
        \curl_setopt($ch, \CURLOPT_POSTFIELDS, $body);
        \curl_setopt($ch, \CURLOPT_HTTPHEADER, [
            'Content-Type: multipart/form-data',
            'Accept: application/json'
        ]);
        $result = \curl_exec($ch);
        if (\curl_errno($ch)) {
            $error_msg = \curl_error($ch);
            throw new \Exception($error_msg);
        }
        \curl_close($ch);
        $result = \json_decode($result);
        if ($result === null && \json_last_error() !== \JSON_ERROR_NONE) {
            throw new \Exception('Malformed JSON Response: '.\json_last_error_msg());
        }
        return $result;
    }

    private function processUpdates() {
        $url = 'https://api.telegram.org/bot'.$this->token.'/getUpdates';

        $updateState = $this->getUpdateState();
        $query = [
            'offset' => $updateState??0
        ];
        $body = \http_build_query($query);

        $ch = \curl_init();
        \curl_setopt($ch, \CURLOPT_URL, $url);
        \curl_setopt($ch, \CURLOPT_POST, 1);
        \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, 1);
        \curl_setopt($ch, \CURLOPT_POSTFIELDS, $body);
        \curl_setopt($ch, \CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Content-Length: ' . \strlen($body),
            'Accept: application/json'
        ]);
        $result = \curl_exec($ch);
        if (\curl_errno($ch)) {
            $error_msg = \curl_error($ch);
            throw new \Exception($error_msg);
        }
        \curl_close($ch);
        $result = \json_decode($result);
        if ($result === null && \json_last_error() !== \JSON_ERROR_NONE) {
            throw new \Exception('Malformed JSON Response: '.\json_last_error_msg());
        }
        if ($result->ok) {
            //\file_put_contents(\APP_DIR . '/var/tmp/telegram-updates.json', \json_encode($result->result));
            if (\count($result->result)>0) {
                foreach ($result->result as $update) {
                    if (\property_exists($update, 'message')) {
                        $message = $update->message;
                        switch ($message->chat->type) {
                            case 'private':
                                if (\property_exists($message, 'from')) {
                                    $from = $message->from;
                                    if (!\array_key_exists('U'.$from->id, $this->users)) {
                                        $this->appendUser($from->id, $from->is_bot, $from->first_name, $from->last_name, $from->username, $from->language_code);
                                    }
                                }
                            break;
                        }
                        if (\property_exists($message, 'entities')) { //if text
                            $entities = $message->entities;
                            foreach ($entities as $entity) {
                                if ($entity->type=='bot_command') {
                                    $command = \substr($message->text, $entity->offset, $entity->length);
                                    $subcmd = null;
                                    $scmdo = $entity->offset+$entity->length;
                                    if (\strlen($message->text)>$scmdo) {
                                        $subcmdc = \substr($message->text, $scmdo);
                                        if (\strpos($subcmdc, ' ')!==false) {
                                            $subcmdc = \substr($subcmdc, 0, \strpos($subcmdc,' ')-1);
                                        }
                                        if (\substr($subcmdc, 0, 1)=='.') {
                                            $subcmd = \substr($subcmdc, 1);
                                        }
                                    }
                                    $fileload = \APP_DIR . '/var/telegramcmds' . $command . '.php';
                                    if (\file_exists($fileload)) {
                                        $cmdmanifest = require $fileload;
                                        $reflect = new \ReflectionClass($cmdmanifest['handler']);
                                        $instance = $reflect->newInstance($this->container);

                                        if ($subcmd!=null) {
                                            $cmd = $cmdmanifest['options']['commands'][$subcmd]??null;
                                            if (\is_array($cmd)) {
                                                $cmd = $cmd['name'];
                                            } else {
                                                $cmd = null;
                                            }
                                            if ($cmd != null) {
                                                $instance->$cmd($message, $this);
                                            }
                                        } else {
                                            $instance->run($message, $this);
                                        }
                                    } else {
                                        if ($message->chat->type=='private') {
                                            $this->sendUserText($message->chat->id, 'command not found', $message->message_id);
                                        }
                                    }
                                }
                            }
                        } else {
                            if ($message->chat->type=='private') {
                                $this->sendUserText($message->chat->id, $message->text, $message->message_id);
                            }
                        }
                    }
                }
                $last = \end($result->result);
                $this->saveUpdateState($last->update_id);
            }
        } else {
            $this->log('getUpdates Error: '.$result->description);
        }
    }

    public function messageChannelText($text) {
        $sql = "INSERT INTO sys_telegram_chqueue (`type`,`text`,state) VALUES (:type,:text,0)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':type' => 0,
            ':text' => $text
        ]);
    }

    public function messageChannelPhoto($file, $caption) {
        $sql = "INSERT INTO sys_telegram_chqueue (`type`,`file`,`caption`,state) VALUES (:type,:file,:caption,0)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':type' => 1,
            ':file' => $file,
            ':caption' => $caption
        ]);
    }

    public function messageUserText($username, $text) {
        $chat_id = $this->findIdByUsername($username);
        if ($chat_id) {
            $sql = "INSERT INTO sys_telegram_uqueue (chat_id,`type`,`text`,state) VALUES (:chat_id,:type,:text,0)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':chat_id' => $chat_id,
                ':type' => 0,
                ':text' => $text
            ]);
        }
    }

    public function messageUserPhoto($username, $file, $caption) {
        $chat_id = $this->findIdByUsername($username);
        if ($chat_id) {
            $sql = "INSERT INTO sys_telegram_uqueue (chat_id,`type`,`file`,`caption`,state) VALUES (:chat_id,:type,:file,:caption,0)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':chat_id' => $chat_id,
                ':type' => 1,
                ':file' => $file,
                ':caption' => $caption
            ]);
        }
    }

    private function getChannelQueue() {
        $sql = "select a.* from sys_telegram_chqueue a where a.state=0 order by a.id limit 0,10";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function getUserQueue() {
        $sql = "select a.* from sys_telegram_uqueue a where a.state=0 order by a.id limit 0,6";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function setChannelMessageResult($id, $messageId, $date) {
        $stmt = $this->db->prepare(
            'UPDATE sys_telegram_chqueue SET message_id=:message_id,`date`=:date,state=1 WHERE id=:id'
        );
        $stmt->execute([
            ':message_id' => $messageId,
            ':date' => $date,
            ':id' => $id
        ]);
    }

    private function setUserMessageResult($id, $messageId, $date) {
        $stmt = $this->db->prepare(
            'UPDATE sys_telegram_uqueue SET message_id=:message_id,`date`=:date,state=1 WHERE id=:id'
        );
        $stmt->execute([
            ':message_id' => $messageId,
            ':date' => $date,
            ':id' => $id
        ]);
    }

    private function setChannelMessageError($id, $logs) {
        $stmt = $this->db->prepare(
            'UPDATE sys_telegram_chqueue SET logs=:logs,state=:state WHERE id=:id'
        );
        $stmt->execute([
            ':logs' => $logs,
            ':state' => self::STATE_ERROR,
            ':id' => $id
        ]);
    }

    private function setUserMessageError($id, $logs) {
        $stmt = $this->db->prepare(
            'UPDATE sys_telegram_uqueue SET logs=:logs,state=:state WHERE id=:id'
        );
        $stmt->execute([
            ':logs' => $logs,
            ':state' => self::STATE_ERROR,
            ':id' => $id
        ]);
    }

    private function saveUpdateState($updateId) {
        $sql = "INSERT INTO sys_configs (k,v) VALUES ('telegram.lastUpdateId',:v) ON DUPLICATE KEY UPDATE v=:v2";
        $stmt = $this->db->prepare($sql);
        $updateId = (int)$updateId;
        $updateId++;
        $stmt->execute([':v' => $updateId, ':v2' => $updateId]);
    }

    private function getUpdateState() {
        $sql = "select a.* from sys_configs a where a.k='telegram.lastUpdateId'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch();
        if (\is_object($row)) {
            return $row->v;
        }
        return null;
    }

    private function appendUser($id, $isBot, $firstName, $lastName, $userName, $languageCode) {
        $sql = "INSERT INTO sys_telegram_users (id,is_bot,first_name,last_name,username,language_code) VALUES (:id,:is_bot,:first_name,:last_name,:username,:language_code) ON DUPLICATE KEY UPDATE is_bot=:is_bot2,first_name=:first_name2,last_name=:last_name2,username=:username2,language_code=:language_code2";
        $stmt = $this->db->prepare($sql);
        $isBot = ($isBot?1:0);
        $userName = ($userName?$userName:null);
        $languageCode = \substr($languageCode,0,2);
        $stmt->execute([
            ':id' => $id,
            ':is_bot' => $isBot,
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':username' => $userName,
            ':language_code' => $languageCode,
            ':is_bot2' => $isBot,
            ':first_name2' => $firstName,
            ':last_name2' => $lastName,
            ':username2' => $userName,
            ':language_code2' => $languageCode
        ]);
    }

    public function findIdByUsername($username) {
        $sql = "select a.* from sys_telegram_users a where a.username=:username";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':username' => $username
        ]);
        $row = $stmt->fetch();
        if (\is_object($row)) {
            return $row->id;
        }
        return null;
    }

    private function createUsersCache($fcache) {
        $sql = "SELECT a.* FROM sys_telegram_users a";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $values = [];
        foreach ($rows as $row) {
            $values['U'.$row->id] = \json_encode($row);
        }

        $fileout = "<?php\nreturn " . CodeOut::fromArray($values) . ';';
        \file_put_contents($fcache, $fileout);
        return $values;
    }

    private function log($error) {
        $logger = $this->container->get('monolog');
        $logger->info($error);
    }
}