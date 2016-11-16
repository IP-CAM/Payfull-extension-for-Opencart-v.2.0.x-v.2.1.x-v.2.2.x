<form class="form-horizontal">
  <fieldset id="payment">
    <legend><?php echo $text_credit_card; ?></legend>
    <div class="form-group required">
      <label class="col-sm-2 control-label" for="input-cc-type"><?php echo $entry_cc_name; ?></label>
      <div class="col-sm-6">
        <input type="text" name="cc_name" value="" placeholder="<?php echo $entry_cc_name; ?>" id="input-cc-name" class="form-control" />
      </div>
      <div class="col-sm-4"></div>
    </div>
    <div class="form-group required">
      <label class="col-sm-2 control-label" for="input-cc-number"><?php echo $entry_cc_number; ?></label>
      <div class="col-sm-6">
        <input type="text" name="cc_number" value="" placeholder="<?php echo $entry_cc_number; ?>" id="input-cc-number" class="input-cc-number-not-supported form-control" maxlength="16" />
      </div>
      <div class="col-sm-4"></div>
    </div>
    <div class="form-group required">
      <label class="col-sm-2 control-label" for="input-cc-start-date"><?php echo $entry_cc_date; ?></label>
      <div class="col-sm-3">
        <select name="cc_month" id="input-cc-start-date" class="form-control">
          <?php foreach ($month_valid as $month) { ?>
          <option value="<?php echo $month['value']; ?>"><?php echo $month['text']; ?></option>
          <?php } ?>
        </select>
      </div>
      <div class="col-sm-3 required">
        <select name="cc_year" class="form-control">
          <?php foreach ($year_valid as $year) { ?>
          <option value="<?php echo $year['value']; ?>"><?php echo $year['text']; ?></option>
          <?php } ?>
        </select>
      </div>
    </div>
    <div class="form-group required">
      <label class="col-sm-2 control-label" for="input-cc-cvv2"><?php echo $entry_cc_cvc; ?></label>
      <div class="col-sm-6">
        <input type="text" name="cc_cvc" value="" placeholder="<?php echo $entry_cc_cvc; ?>" id="input-cc-cvc" class="form-control" />
      </div>
      <div class="col-sm-4"></div>
    </div>

    <div class="form-group installments-wrapper">
      <label class="col-sm-2 control-label" for="input-cc-start-date"><?php echo $text_installments; ?></label>
      <div class="col-sm-6">
          <div id="installment_table_id">
              <div class="installmet_head">
                  <div class="install_head_label add_space"><img style="display: none" class="bank_photo" data-src="<?php echo $payfull_banks_images; ?>" src=""></div>
                  <div class="install_head_label"><?php echo $entry_payfull_installmet; ?></div>
                  <div class="install_head_label"><?php echo $entry_payfull_amount; ?></div>
                  <div class="install_head_label"><?php echo $entry_payfull_total; ?></div>
              </div>
              <div class="installment_body" id="installment_body">
                  <div class="installment_row">
                      <div class="install_body_label installment_radio"><input rel="1" type="radio" class="installment_radio" checked name="installments" value="1" /></div>
                      <div class="install_body_label installment_lable_code"><?php echo $text_one_shot; ?></div>
                      <div class="install_body_label"><?php echo $total; ?></div>
                      <div class="install_body_label final_commi_price" rel="<?php echo $total; ?>"><?php echo $total; ?></div>
                  </div>
              </div>
              <div class="installment_footer"></div>
          </div>
      </div> 
    </div>

      <div class="form-group  extra_installments_container" style="display: none;">
          <div class="col-sm-3 col-sm-offset-2">
              <label><?php echo $text_extra_installments; ?></label>
              <div class="extra_installments_select"></div>
          </div>
          <div class="col-sm-9 col-sm-offset-2">
          </div>
      </div>

    <input name="use3d" type="hidden" value="0" />

    <div class="form-group use-3d-wrapper" style="<?php echo ($payfull_3dsecure_status==0)?'display: none':''; ?>">
      <div class="col-sm-10 col-sm-offset-2">
      <div class="checkbox">
        <label><input name="use3d" id="use3d" type="checkbox" value="1"><?php echo $text_3d; ?></label>
      </div>
      </div>
    </div>

  </fieldset>
</form>
<div class="buttons">
  <div class="pull-right">
    <input type="button" value="<?php echo $button_confirm; ?>" id="button-confirm" data-loading-text="<?php echo $text_loading; ?>" class="btn btn-primary" />
  </div>
</div>
<script type="text/javascript"><!--
    BIN_GLOBAL_FOR_CARD = '00000';
    INSTALMENTS_RESET   = true;

    var cardNumberFiledSelector = $('#input-cc-number');
    var confirmButton           = $('#button-confirm');

    cardNumberFiledSelector.keyup(function(){
        var BIN = $(this).val();
        BIN = BIN.replace(/ /g,'');
        cardBrandDetector(BIN);

        if(BIN.length > 5){
            BIN               = BIN.substring(0, 6);
            INSTALMENTS_RESET = true;
            if(BIN == BIN_GLOBAL_FOR_CARD){
                //return
            }else{
                BIN_GLOBAL_FOR_CARD = BIN;
                refreshInstallmentOptions();
            }
        }else{
            BIN_GLOBAL_FOR_CARD = BIN;
            if(INSTALMENTS_RESET){
                refreshInstallmentOptions();
                INSTALMENTS_RESET = false;
            }

        }

  });

    confirmButton.bind('click', function() {
  $.ajax({
    url: 'index.php?route=payment/payfull/send',
    type: 'post',
    data: $('#payment select, #payment input[type="text"], #payment input[type="hidden"], #payment input[type="checkbox"]:checked, #payment input[type="radio"]:checked' ),
    dataType: 'json',

    beforeSend: function() {
      $('.alert').remove();
      $('#button-confirm').attr('disabled', true);
      $('#payment').before('<div class="alert alert-info"><i class="fa fa-info-circle"></i> <?php echo $text_wait; ?></div>');
    },

    complete: function() {
      $('#button-confirm').attr('disabled', false);
      $('.attention').remove();
    },

    success: function(json) {
        if (json['success']) {
            location = json['success'];
            return true;
        }

        $('.alert').remove();

      if (json['error']['general_error']) {
        $('#payment').after('<div class="alert alert-warning"><i class="fa fa-info-circle"></i> '+json['error']['general_error']+'</div>');
      }

      if (json['error']['cc_name']) {
          $('#input-cc-name').after('<div class="alert alert-warning"><i class="fa fa-info-circle"></i> '+json['error']['cc_name']+'</div>');
      }

      if (json['error']['cc_number']) {
        $('#input-cc-number').after('<div class="alert alert-warning"><i class="fa fa-info-circle"></i> '+json['error']['cc_number']+'</div>');
      }

      if (json['error']['cc_month']) {
        $("select[name='cc_month']").after('<div class="alert alert-warning"><i class="fa fa-info-circle"></i> '+json['error']['cc_month']+'</div>');
      }

      if (json['error']['cc_year']) {
        $("select[name='cc_year']").after('<div class="alert alert-warning"><i class="fa fa-info-circle"></i> '+json['error']['cc_year']+'</div>');
      }

      if (json['error']['cc_cvc']) {
        $('#input-cc-cvc').after('<div class="alert alert-warning"><i class="fa fa-info-circle"></i> '+json['error']['cc_cvc']+'</div>');
      }
    }
  });
});

    function cardBrandDetector(number) {
        cardNumberFiledSelector.removeClass('input-cc-number-not-supported');
        var re_visa = new RegExp("^4");
        var re_master = new RegExp("^5[1-5]");
        if (number.match(re_visa) != null){
            cardNumberFiledSelector.addClass('input-cc-number-visa');
            cardNumberFiledSelector.removeClass('input-cc-number-master');
        }else if (number.match(re_master) != null){
            cardNumberFiledSelector.removeClass('input-cc-number-visa');
            cardNumberFiledSelector.addClass('input-cc-number-master');
        }else{
            cardNumberFiledSelector.removeClass('input-cc-number-visa');
            cardNumberFiledSelector.removeClass('input-cc-number-master');
            cardNumberFiledSelector.addClass('input-cc-number-not-supported');
        }
    }

    function refreshInstallmentOptions(){
        $.ajax({
            url: 'index.php?route=payment/payfull/get_card_info',
            type: 'post',
            data: $('#payment :input'),
            dataType: 'json',

            beforeSend: function() {
                $('.alert').remove();
                $('#button-confirm').attr('disabled', true);
                $('#payment').before('<div class="alert alert-info"><i class="fa fa-info-circle"></i> <?php echo $text_wait; ?></div>');
            },

            complete: function() {
                $('#button-confirm').attr('disabled', false);
                $('.attention').remove();
            },

            success: function(json) {
                $('.alert').remove();
                if(json['has3d'] == 1){
                    $('.use-3d-wrapper').css('display','block');
                }else{
                    $('.use-3d-wrapper').css('display','none');
                }

                var $bank_photo = $('.bank_photo');
                if(json['bank_id'].length > 0){
                    if(json['card_type'] == 'CREDIT'){
                        $bank_photo.attr('src', $bank_photo.attr('data-src')+'networks/'+json['bank_id']+'.png');
                    }else{
                        $bank_photo.attr('src', $bank_photo.attr('data-src')+'banks/'+json['bank_id']+'.png');
                    }

                    $bank_photo.show();
                }else{
                    $bank_photo.hide();
                }

                var $options          = $('#installment_body');
                $options.show();
                $options.html('');
                var oneShotCount      = 1;
                var oneShotInsTotal   = json['installments']['0']['installment_total'];
                var oneShotTotal      = json['installments']['0']['total'];
                var oneShotSelected   = 1;
                $options.append(getInstallementOption(oneShotCount, oneShotInsTotal, oneShotTotal, oneShotSelected, ''));
                if(json['installments'].length > 0 && json['card_type'] == 'CREDIT'){
                    for($i=2; $i < json['installments'].length; $i++){
                        var installment_total       = json['installments'][$i]['installment_total'];
                        var count                   = json['installments'][$i]['count'];
                        var total                   = json['installments'][$i]['total'];
                        var hasExtra                = json['installments'][$i]['hasExtra'];
                        $options.append(getInstallementOption(count, installment_total, total, 0, json['bank_id'], hasExtra));
                    }
                }
            }
        });
    };

    function getInstallementOption(count, instalment_total, total, checked, bank_id, hasExtra) {
        if(checked) checked = 'checked="checked"';
        else checked = '';

        var textOfCount = count==1?'<?php echo $text_one_shot; ?>' : count;
        textOfCount     = hasExtra=='1'?'<span class="joker">'+count+' + Joker</span>' : textOfCount;

        return ''
                + '<div class="installment_row">'
                + '<div class="install_body_label installment_radio">'
                + '<input data-bank-id="'+bank_id+'" rel="'+count+'" class="custom_field_installment_radio" type="radio" '+checked+' name="installments" value="'+count+'" />'
                + '</div>'
                + '<div class="install_body_label installment_lable_code">'+textOfCount+'</div>'
                + '<div class="install_body_label">'+ instalment_total + '</div>'
                + '<div rel="' + total + '" class="install_body_label final_commi_price">' +total + '</div>'
                + '</div>'
                ;
    }

    $(document).on('click', '.installment_radio', function() {
        var selectedInstallmentCount = $("input[name='installments']:checked").val();
        var selectedInstallmentBank  = $("input[name='installments']:checked").attr('data-bank-id');
        getExtraInstallments(selectedInstallmentCount, selectedInstallmentBank);

    });
    function getExtraInstallments(selectedInstallmentCount, selectedInstallmentBank) {

        var divSelectorExtraInst  = $('.extra_installments_container');
        var containerSelectorInst = $('.extra_installments_select');

        $.ajax({
            url: 'index.php?route=payment/payfull/get_extra_installments&inst='+selectedInstallmentCount+'&bank='+selectedInstallmentBank,
            type: 'post',
            data: $('#payment :input'),
            dataType: 'json',

            beforeSend: function() {
                $('.alert').remove();
                $('#button-confirm').attr('disabled', true);
                $('#payment').before('<div class="alert alert-info"><i class="fa fa-info-circle"></i> <?php echo $text_wait; ?></div>');
            },

            complete: function() {
                $('#button-confirm').attr('disabled', false);
                $('.attention').remove();
            },

            success: function(json) {
                $('.alert').remove();
                if(json['extra_inst'] != ''){
                    var selectExtraInstallments = "<select name='campaign_id' class='form-control'>";
                    selectExtraInstallments = selectExtraInstallments+'<option value=""><?php echo $text_select_extra_inst;?></option>';
                    var extra_inst = json['extra_inst'];
                    $.each(extra_inst, function( index, value ) {
                        var option = '<option value="'+value+'">+ '+index+'</option>';
                        selectExtraInstallments = selectExtraInstallments+option;
                    });
                    selectExtraInstallments = selectExtraInstallments+'</select>';
                    containerSelectorInst.html(selectExtraInstallments);
                    divSelectorExtraInst.css('display', 'block');
                }else{
                    containerSelectorInst.html('');
                    divSelectorExtraInst.css('display', 'none');
                }
            }
        });
    }

//--></script>

<style>
  .input-cc-number-visa {
    background: rgba(0, 0, 0, 0) url("<?php echo $visa_img_path; ?>") no-repeat scroll right center / 12% auto;
    float: left;
  }

  .input-cc-number-master {
    background: rgba(0, 0, 0, 0) url("<?php echo $master_img_path; ?>") no-repeat scroll right center / 12% auto;
    float: left;
  }

  .input-cc-number-not-supported {
    background: rgba(0, 0, 0, 0) url("<?php echo $not_supported_img_path; ?>") no-repeat scroll right center / 8% auto;
    float: left;
  }


.card_loder > img {display: inline;vertical-align: middle;width: 25px;}
.card_image > img {display: inline-block;width: auto;height:25px;vertical-align: middle;}
.card_image { display: inline-block;  padding:0 5px;vertical-align: bottom;}
.toatl_label h3 {margin: 15px 0 0 0;}
.install_body_label {float: left;width: 30%;height: 40px;text-align: center; border-bottom: 1px solid #d2d2d2;line-height: 40px;}
.installment_row {/* padding-top: 10px;*/}
.install_body_label.installment_radio, .installmet_head .install_head_label.add_space {height: 40px;text-align: center;width: 10%;line-height: 40px;}
#installment_table_id {background-color: #eee;border: 1px solid;border-radius: 5px;padding: 10px;margin-top: 20px;}
.installmet_head .install_head_label {float: left;font-weight: bold;text-align: center;width: 30%; height: 40px;line-height: 40px;border-bottom: 2px solid #d2d2d2; }
.installment_body , .installment_footer {  clear: both; }
.toatl_label {display:  none;}
.bank_photo {height: 32px !important;}
.joker {
    border-radius: 25px;
    font-weight: 600;
    padding: 3px 10px;
    background: #ff9800;
    color: white;
    text-transform: uppercase;
}
</style>