<label>[{ oxmultilang ident="NOVALNET_REDIRECT_MESSAGE" }]</label>
<form action="[{$sNovalnetFormAction}]" id="novalnet_redirect_form" method="post">
    [{foreach key=sNovalnetKey from=$aNovalnetFormData item=sNovalnetValue}]
        <input type="hidden" name="[{$sNovalnetKey}]" value="[{$sNovalnetValue}]" />
     [{/foreach}]
    <input type="submit" value="[{oxmultilang ident='NOVALNET_REDIRECT_SUBMIT'}]" />
</form>
<script type="text/javascript">document.forms[0].submit();</script>
