[{include file="layout/header.tpl"}]
    <div id="wrapper" style="height:420px;padding:2em;">
	<label>[{ oxmultilang ident="NOVALNET_REDIRECT_MESSAGE" }]</label>
		 <form action="[{$sNovalnetFormAction}]" id="novalnet_redirect_form" method="post">
			[{foreach key=sNovalnetKey from=$aNovalnetFormData item=sNovalnetValue}]
			<input type="hidden" name="[{$sNovalnetKey}]" value="[{$sNovalnetValue}]" />
			[{/foreach}]
			<input type="submit" id="Submit1" class="btn btn-primary" value="[{oxmultilang ident='NOVALNET_REDIRECT_SUBMIT'}]" />
			</form>
    <script type="text/javascript">setTimeout(function(){ document.getElementById('Submit1').click(); }, 500);</script>
    </div>
[{include file="layout/footer.tpl"}]    
[{include file="layout/base.tpl"}]
