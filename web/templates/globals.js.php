const globals = {
    basePath: '<?=BASE_PATH?>',
    backendPath: '<?=$settings['backend_path']?>',
    resTypes: {<?php
$type = [];
foreach (Albatiqy\Slimlibs\Result\AbstractResult::RES_TYPES as $i=>$v) {
    $type[] = $i.':'.$v;
}
echo implode(', ', $type);
?>},
    errTypes: {<?php
$type = [];
foreach (Albatiqy\Slimlibs\Result\ResultException::ERR_TYPES as $i=>$v) {
    $type[] = $i.':'.$v;
}
echo implode(', ', $type);
?>}
}
export default globals