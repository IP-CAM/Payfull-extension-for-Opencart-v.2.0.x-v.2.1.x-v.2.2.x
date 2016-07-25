<form class="form-horizontal">
  <fieldset id="payment">
    <legend><?php echo $text_credit_card; ?></legend>
    <div class="form-group required">
      <label class="col-sm-2 control-label" for="input-cc-type"><?php echo $entry_cc_name; ?></label>
      <div class="col-sm-10">
        <input type="text" name="cc_name" value="" placeholder="<?php echo $entry_cc_name; ?>" id="input-cc-name" class="form-control" />
      </div>
    </div>
    <div class="form-group required">
      <label class="col-sm-2 control-label" for="input-cc-number"><?php echo $entry_cc_number; ?></label>
      <div class="col-sm-10">
        <input type="text" name="cc_number" value="" placeholder="<?php echo $entry_cc_number; ?>" id="input-cc-number" class="form-control" />
      </div>
    </div>
    <div class="form-group">
      <label class="col-sm-2 control-label" for="input-cc-start-date"><?php echo $entry_cc_date; ?></label>
      <div class="col-sm-3">
        <select name="cc_month" id="input-cc-start-date" class="form-control">
          <?php foreach ($month_valid as $month) { ?>
          <option value="<?php echo $month['value']; ?>"><?php echo $month['text']; ?></option>
          <?php } ?>
        </select>
      </div>
      <div class="col-sm-3">
        <select name="cc_year" class="form-control">
          <?php foreach ($year_valid as $year) { ?>
          <option value="<?php echo $year['value']; ?>"><?php echo $year['text']; ?></option>
          <?php } ?>
        </select>
      </div>
    </div>
    <div class="form-group required">
      <label class="col-sm-2 control-label" for="input-cc-cvv2"><?php echo $entry_cc_cvc; ?></label>
      <div class="col-sm-10">
        <input type="text" name="cc_cvc" value="" placeholder="<?php echo $entry_cc_cvc; ?>" id="input-cc-cvc" class="form-control" />
      </div>
    </div>

    <div class="form-group installments-wrapper">
      <label class="col-sm-2 control-label" for="input-cc-start-date"><?php echo $text_installments; ?></label>
      <div class="col-sm-3">
        <select name="installments" class="form-control">
        <option>1</option>
        </select>
      </div> 
    </div>

    <input name="use3d" type="hidden" value="0" />

    <div class="form-group use-3d-wrapper" style="display: none;">
      <div class="col-sm-10 col-sm-offset-2">
      <div class="checkbox">
        <label><input name="use3d" type="checkbox" value="1"><?php echo $text_3d; ?></label>
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

$('#input-cc-number').on('change', function(){

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

      if(json['installments'].length > 0){

          $html = '';

          for($i=0; $i < json['installments'].length; $i++){
            $html += '<option>'+json['installments'][$i]['count']+'</option>';
          }

          $('.installments-wrapper').css('display', 'block');
          $('.installments-wrapper select').html($html);
      }else{
          $('.installments-wrapper').css('display', 'block');
          $('.installments-wrapper select').html('<option>1</option>');
      }

      if (json['success']) {
       // location = json['success'];
      }
    }
  });
});

$('#button-confirm').bind('click', function() {
  $.ajax({
    url: 'index.php?route=payment/payfull/send',
    type: 'post',
    data: $('#payment select, #payment input[type="text"], #payment input[type="hidden"], #payment input[type="checkbox"]:checked'),
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

      if (json['error']) {
          $('#payment').before('<div class="alert alert-warning"><i class="fa fa-info-circle"></i> '+json['error']+'</div>');
      }

      if (json['success']) {
         location = json['success'];
      }
    }
  });
});
//--></script>