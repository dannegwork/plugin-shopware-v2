<div class="ph--nl container">
    <div class="col2">
        <div class="pl--nl-headline">
            {$headline}
            <span>  {$headline_span}</span>
        </div>
    </div>
    <div class="col2">
        {*<script language="javascript">function isEmail(emailAddress){emailAddressValue=emailAddress.value;var emailAddressRegex=/^[^@\s]+@[^\.@\s]+(\.[^@\s^\.]+)+$/;if(emailAddress.value==''||emailAddress==null){return true;}if(!emailAddressRegex.test(emailAddressValue)){alert('Bitte überprüfen Sie die korrekte Eingabe Ihrer Email Adresse.');emailAddress.focus();return false;}return true;}String.prototype.trim=function(){return this.replace(/^\s*(\b.*\b|)\s*$/,"$1");};function mandatoryText(input,fieldName){if(input.value.trim()==''||input==null){alert('Bitte füllen Sie das Feld '+fieldName+'.');input.focus();return false;}else{return true;}}function validForm(){if(!mandatoryText(document.getElementById('EMAIL_FIELD'),'E-Mail'))return;if(!isEmail(document.getElementById('EMAIL_FIELD')))return;document.getElementById('emvForm').submit();}</script>*}
        <div class="ph--nl-hint active">{$percent}</div>
        <form class="newsletter--form" name="emvForm" id="emvForm" action="https://p3trc.emv2.com/D2UTF8" method="POST" target="_top">
            <input name="emv_tag" value="A0200008D2F5A14A" type="hidden">
            <input name="emv_ref" value="BYmMpDFvJxPjuNxxpb0I3kUQflC6kO1U-lKXL-hDnTNliQpzwH0uDNR8fw4Rx5DE/c5bgoqa9Fwme9ui5ZsRzpQ" type="hidden">
            <input id="EMAIL_FIELD" name="EMAIL_FIELD" placeholder="{$textfield_ph}" class="newsletter--field" type="text">
            <input id="LANGUAGE_FIELD" name="LANGUAGE_FIELD" value="DE" type="hidden">
            <input class="newsletter--button btn" value="{$button_text}" onclick="javascript:validForm();" type="button">
            <input id="SOURCE_FIELD" name="SOURCE_FIELD" value="Webform-Anmeldung" type="hidden">
            <input name="__csrf_token" value="HGti6bJGkuHdaqcJjCDkPSEhvVR9vH" type="hidden"></form>
    </div>
</div>