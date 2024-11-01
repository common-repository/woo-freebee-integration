(function( $ ) {
	'use strict';
	if ($('body').is('.woocommerce-checkout')){
		if ($('#freebee_il_customer_card_number').length){
			freebeeIlChangeCheckoutInputColor();
		}
	}

	function freebeeIlChangeCheckoutInputColor() {
		$( "#freebee_il_customer_card_number" ).change(function() {
			var lengthValue = $(this).val().length;
			if (lengthValue == 9 || lengthValue == 0){
				$(this).css('box-shadow','inset 2px 0 0 #0f834d');
			} else {
				$(this).css('box-shadow','inset 2px 0 0 #e2401c');
			}
		});
	}
})( jQuery );
