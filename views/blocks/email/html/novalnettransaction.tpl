[{if in_array($payment->oxuserpayments__oxpaymentsid->value, array('novalnetcreditcard', 'novalnetsepa', 'novalnetinvoice', 'novalnetprepayment', 'novalnetonlinetransfer', 'novalnetpaypal', 'novalnetideal', 'novalneteps', 'novalnetgiropay', 'novalnetprzelewy24','novalnetbarzahlen')) }]
    [{ $order->oxorder__novalnetcomments->value|html_entity_decode }]
[{/if}]
<br><br>
[{ $smarty.block.parent }]
