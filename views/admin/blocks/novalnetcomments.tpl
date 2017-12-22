[{$smarty.block.parent}]
[{if isset($aNovalnetPayments) && $paymentType->oxuserpayments__oxpaymentsid->value|in_array:$aNovalnetPayments}]
    <tr>
        <td class="edittext" colspan="2" style="word-wrap: break-word; white-space: pre-wrap;"><br>[{if $edit->oxorder__novalnet_transaction->value != ''}][{ oxmultilang ident="NOVALNET_TRANSACTION_DETAILS" }][{$edit->oxorder__novalnet_transaction->value|html_entity_decode}][{/if}][{if $edit->oxorder__novalnet_comments->value != ''}][{$edit->oxorder__novalnet_comments->value|html_entity_decode}][{/if}]<br>[{$edit->oxorder__novalnetcomments->value|html_entity_decode}]
        </td>
    </tr>
[{/if}]
