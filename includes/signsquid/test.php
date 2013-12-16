<?php
require_once("common.php");
echo '<h1>Page de test</h1>';


echo '<h2>Creating a contract</h2>';
//get_location_from_headers('HTTP/1.1 201 Created Cache-Control: no-cache Pragma: no-cache Expires: -1 Location: https://app.signsquid.com/api/v1/contracts/52af080d4bdcc84874e718a0 Server: Microsoft-IIS/7.5 X-AspNet-Version: 4.0.30319 X-Powered-By: ASP.NET Date: Mon, 16 Dec 2013 14:02:53 GMT Content-Length: 0');
$new_contract_id = signsquid_create_contract('Emilien 16h16 - Investissement de 12€ de Emil Sc (emilien.schneider@poucr.net) - Le 16 décembre 2013');
//$new_contract_id = '529767f34bdcc80a782fd32a';
//$new_contract_id = '529ca8214bdcc8532856f1bc';
echo '<br />$new_contract_id = ' . $new_contract_id;


echo '<h2>Adding a signatory</h2>';
signsquid_add_signatory($new_contract_id, 'Emil Schn', 'emilien@wedogood.co');


echo '<h2>Getting a contract</h2>';
//$infos = signsquid_get_contract_infos($new_contract_id);
//print_r($infos);


echo '<h2>Sending a file</h2>';
$pdf_filename = 'test.pdf';
signsquid_add_file($new_contract_id, $pdf_filename);


echo '<h2>Getting all contracts</h2>';
//$list = signsquid_get_contract_list();
//print_r($list);


echo '<h2>Sending invitations</h2>';
signsquid_send_invite($new_contract_id);

?>