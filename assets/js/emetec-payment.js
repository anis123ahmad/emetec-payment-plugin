jQuery( function( $ ) {
    
	const $checkout_form = $( 'form.checkout, form#order_review' );
    let scriptPaymentWidget;
    let wpwlContainerCardClass;

    window.wpwlOptions = {
        style:"card",
        //locale: "es",
        maskCvv: true,
        disableSubmitOnEnter: true,
        useSummaryPage: true,
        onReady: function(e){
            $('.wpwl-form-card').find('.wpwl-group-submit').hide();
            let classFormDiv = $checkout_form.find('.wpwl-container').attr('class');
            let regex = /wpwl-container-card-\d+/;
            let matches = classFormDiv.match(regex);

            if (matches && matches.length > 0) {
                wpwlContainerCardClass = matches[0];
            }

        },
        onSaveTransactionData: function(data) {
            //console.log(data);
        },
        onBeforeSubmitCard: function(e){
            return validateHolder(e);
        },
        onBeforeSubmitDirectDebit: function (event){
            return false;
        },
        onAfterSubmit: function (e){
            validateHolder(e);
        },
        onError: function (error){
            console.log(error)
        }
    }

    function validateHolder(){
        let holder = $('.wpwl-control-cardHolder');
        if (holder.val().trim().length < 2){
            holder.addClass('wpwl-has-error').after('<div class="wpwl-hint wpwl-hint-cardHolderError">Titular de tarjeta no válido</div>');
            return false;
        }
        return true;
    }


    $( document.body ).on( 'updated_checkout', function() {
        loadCard();
    });


    $( document.body ).on( 'payment_method_selected', function() {
        loadCard();
    });

    function emetecPaymentFormHandler(){

        if ($checkout_form.find( 'input[name="payment_method"]:checked' ).val()  !== emetec_checkout.idPayment) return true;

        if (!emetec_checkout.checkoutId) return false;

        if (!$('input[name="emetec_checkout_id"]').length){
            $checkout_form.append($('<input name="emetec_checkout_id" type="hidden" />' ).val( emetec_checkout.checkoutId ));
        }

        window.wpwl.executePayment(wpwlContainerCardClass);

        return validateHolder();
    }

    function loadCard() {
        const payment_selected = $checkout_form.find( 'input[name="payment_method"]:checked' ).val();

        if (payment_selected === emetec_checkout.idPayment && !scriptPaymentWidget){

            $( '.payment_method_' + emetec_checkout.idPayment ).block( {
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6,
                },
            } );

            scriptPaymentWidget = document.createElement("script");
            scriptPaymentWidget.type = "text/javascript";
            scriptPaymentWidget.src = emetec_checkout.srcPaymentWidget;
            scriptPaymentWidget.onload = function (){
                $( '.payment_method_' + emetec_checkout.idPayment ).unblock();
            }
            document.body.appendChild(scriptPaymentWidget);
        }
    }

    $( 'form.checkout' ).on( 'checkout_place_order', emetecPaymentFormHandler );
    // Pay Page Form
    $( 'form#order_review' ).on( 'submit', emetecPaymentFormHandler );

});
