/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
/*!**************************************!*\
  !*** ./resources/js/authorizenet.js ***!
  \**************************************/
function executePayment(app) {
  if (app.paymentProcessor === 'stripe' && app.paymentProcessors.stripe) {
    var payload = {
      processor: "stripe",
      coupon: app.couponRes.status ? app.couponRes.coupon.code : null,
      custom_amount: app.customAmount
    };
    var route = app.subscriptionId !== null ? props.routes.subscriptionPayment : props.routes.payment;

    if (app.subscriptionId == null) {
      payload.cart = JSON.stringify(app.cart);
    } else {
      payload.subscription_id = app.subscriptionId;
    }

    try {
      $.post(route, payload, null, 'json').done(function (data) {
        if (data.hasOwnProperty('user_message')) {
          app.showUserMessage(data.user_message, e);
          return;
        }

        if (data.status) {
          location.href = data.redirect;
          return;
        }

        stripe.redirectToCheckout({
          sessionId: data.id
        }).then(function (result) {
          app.showUserMessage(result.error.message, e);
        });
      });
    } catch (err) {
      app.showUserMessage(err, e);
    }
  }
}
/******/ })()
;