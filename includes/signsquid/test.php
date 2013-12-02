<?php
require_once("common.php");
echo '<h1>Page de test</h1>';


echo '<h2>Creating a contract</h2>';
//$new_contract_id = signsquid_create_contract('My test');
//$new_contract_id = '529767f34bdcc80a782fd32a';
$new_contract_id = '529ca8214bdcc8532856f1bc';
echo '<br />$new_contract_id = ' . $new_contract_id;


echo '<h2>Adding a signatory</h2>';
//signsquid_add_signatory($new_contract_id, 'Emil Schn', 'emilien@wedogood.co');

//signsquid_send_invite($new_contract_id);


echo '<h2>Getting a contract</h2>';
$infos = signsquid_get_contract_infos($new_contract_id);
print_r($infos);


echo '<h2>Sending a file</h2>';
$pdf_filename = 'test.pdf';
//signsquid_add_file($new_contract_id, $pdf_filename);


echo '<h2>Getting all contracts</h2>';
$list = signsquid_get_contract_list();
print_r($list);
?>