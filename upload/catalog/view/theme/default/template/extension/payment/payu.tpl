<style type="text/css">
    #payu-pay {cursor: pointer}
    #payu-error {color: #CC0000; font-weight: bold}
</style>

<div class="pull-right">
    <img src="<?php echo $payu_button; ?>" id="payu-pay"/>
</div>
<div id="payu-error"></div>
<script type="text/javascript"><!--
    var isClicked = false;
    $('#payu-pay').on('click', function() {

        if (isClicked === false) {

            isClicked = true;

            $.ajax({
                type: 'get',
                url: '<?php echo $action; ?>',
                cache: false,
                dataType: 'json',
                beforeSend: function () {
                    $('#payu-error').empty();
                    $('#payu-pay').css('cursor', 'wait');
                },
                complete: function () {
                    $('#payu-pay').css('cursor', 'pointer');
                },
                success: function (ret) {
                    if (ret.status === 'SUCCESS') {
                        location = ret.redirectUri
                    } else {
                        $('#payu-error').empty().append(ret.message);
                        isClicked = false;
                    }
                }
            });
        }
    });//--></script>
