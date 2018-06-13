[{if !isset($aNovalnetPayments) || !$paymentType->oxuserpayments__oxpaymentsid->value|in_array:$aNovalnetPayments}]
    [{$smarty.block.parent}]
[{/if}]
