[{$smarty.block.parent}]
[{if in_array($payment->oxpayments__oxid->value, array( 'novalnetcreditcard', 'novalnetsepa', 'novalnetinvoice', 'novalnetprepayment', 'novalnetonlinetransfer', 'novalnetpaypal', 'novalnetideal', 'novalneteps', 'novalnetgiropay', 'novalnetprzelewy24','novalnetbarzahlen' ))}]
    [{oxscript add="$('input[value=order]').closest('form').find(':submit').click(function(){ $(this).attr('disabled', true); $(this).closest('form').submit(); });"}]
[{/if}]
