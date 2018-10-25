<div class="voucherWrapper"
     style="height: 222px;
     max-width: 1160px;
     background-image:url({$background_image})">
    <div class="voucherLeft" style="width:10%;float:left;writing-mode:vertical-rl;transform:rotate(180deg);position: relative;left: 40px;font-size:17px;color:white;text-align:center;height:100%;padding:4% 0%02% 0%">
        <div class="voucherInfo">* Ausgenommen Tools </div>
    </div>
    <div class="voucherCentre" style="width:80%;float:left;text-align:center;position:relative;top:28%;color:white">
        <div class="voucherTop" style="font-size:40px;font-family: AngleciaProDisplay-SemiBold;">Wir schenken Ihnen CHF {$voucher.value}</div>
        <div class="voucherMiddle" style="font-size:22px">Kaufen Sie f&uuml;r CHF {$voucher.minimumcharge} ein und wir schenken Ihnen CHF {$voucher.value} mit dem Code {$voucher.vouchercode}</div>
        <br>
        <div class="voucherButton" style="font-size:14px;"><button type="button" name="button" style="background-color: #a0a0b6;border: none;padding: 5px 20px;text-transform: uppercase;">code kopieren</button>
        </div>
    </div>
    <div class="voucherRight" style="width: 10%; float: right; writing-mode: vertical-rl; right: 40px; position:relative; top:60px;font-size:17px;color:white;text-align:center">
        <div class="voucherDate">G&uuml;ltig bis {$voucher.valid_from}</div>
    </div>
</div>