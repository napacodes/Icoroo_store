require('./bootstrap');

window.app = new Vue(
{
	el: '#app',
	data: {
		appName: props.appName || '',
		route: '',
		cartItems: parseInt(store2.get('cartItems', 0)),
		cart: store2.get('cart', []),
		transactionMsg: props.transactionMsg || '',
		product: props.product || {},
		products: props.products || {},
		licenseId: props.licenseId || null,
		favorites: {},
		canShare: canShare(),
		deviceIsMobile: false,
		liveSearchItems: [],
		recentlyViewedItems: {},
		activeScreenshot: props.activeScreenshot,
		couponRes: {status: false},
		couponValue: 0,
		paymentProcessor: props.paymentProcessor || '',
		paymentFees: props.paymentFees,
		minimumPayments: props.minimumPayments,
		customAmount: '',
		minimumItemPrice: props.minimumItemPrice || null,
		customItemPrice: null,
		totalAmount: 0,
		location: props.location,
		locale: 'en',
		groupBuyBuyers: 0,
		subscriptionId: props.subscriptionId || null,
		subscriptionPrice: props.subscriptionPrice || null,
		paymentProcessors : props.paymentProcessors,
		subcategories: props.subcategories,
		categories: props.categories,
		countryNames: props.countryNames || [],
		pages: props.pages,
		itemId: props.itemId || null,
		itemPrices: props.itemPrices || null,
		itemPromoPrice: null,
		itemHasPromo: false,
		itemIsFree: false,
		itemFreeTime: null,
		currency: props.currency,
		folderFileName: null,
		folderClientFileName: null,
		usersReactions: {},
		usersReaction: '',
		userMessage: '',
		cookiesAccepted: true,
		replyTo: {userName: null, commentId: null},
		commentId: null,
		reviewId: null,
		itemReviewed: props.itemReviewed || false,
		folderContent: null,
		parsedQueryString: {},
		translation: translation || {},
		userCurrency: props.userCurrency,
		currencyPos: props.currency.position,
		exchangeRate: props.exchangeRate,
		currencyDecimals: props.currencyDecimals,
		currencies: props.currencies,
		creditsOrder: {},
		couponFormVisible: false,
		guestItems: {},
		keycodes: props.keycodes || {},
		guestAccessToken: '',
		usersNotifRead: null,
		usersNotif: props.usersNotif || '',
		userNotifs: props.userNotifs || [],
		hasNotifPermission: false,
		previewIsPlaying: false,
		previewTrack: null,
		prepaidCreditsPackId: null,
		realtimeViews: {
			website: 0,
			product: 0
		},
		menu: {
			mobile: {
				type: null,
				selectedCategory: null,
				submenuItems: null,
				hidden: true
			},
			desktop: {
				selectedCategory: null,
				submenuItems: null,
				itemsMenuPopup: {top: 97, left: 0}
			}
		},
		recentPurchase: {},
	},
	methods: {
		mainMenuBack: function()
		{
			Vue.set(this.menu, 'mobile', Object.assign({... this.menu.mobile}, {
									type: this.menu.mobile.selectedCategory !== null ? 'categories' : null,
									selectedCategory: null,
									submenuItems: this.menu.mobile.selectedCategory !== null ? this.categories : null
								}));
		},
		setSubMenu: function(e, categoryIndex, mobileMenu = false, type = null)
		{
			if(categoryIndex === null)
				return;

			if(mobileMenu)
			{
				this.menu.mobile.type = type;

				if(type === 'categories')
				{
					this.menu.mobile.submenuItems = this.categories;
				}
				else if(type === 'subcategories')
				{
					if(Object.keys(this.subcategories).indexOf(categoryIndex.toString()) >= 0)
					{
						this.menu.mobile.selectedCategory = this.categories[categoryIndex];
						this.menu.mobile.submenuItems = this.subcategories[categoryIndex];
					}
					else
					{
						this.menu.mobile.type = 'categories';
						
						this.location.href = this.setProductsRoute(this.categories[categoryIndex].slug);
					}
				}
				else if(type === 'pages')
				{
					this.menu.mobile.selectedCategory = null;
					this.menu.mobile.submenuItems = this.pages;
				}
				else if(type === 'languages')
				{
					this.menu.mobile.selectedCategory = null;
					this.menu.mobile.submenuItems = null;
				}
			}
			else
			{ 
				var isSame = categoryIndex == getObjectProp(this.menu.desktop.selectedCategory, 'id');

				if(Object.keys(this.subcategories).indexOf(categoryIndex.toString()) >= 0)
				{
					Vue.set(this.menu, 'desktop', Object.assign({... this.menu.desktop}, {
										selectedCategory: isSame ? null : this.categories[categoryIndex],
										submenuItems: isSame ? null : this.subcategories[categoryIndex],
										itemsMenuPopup: {top: 97, left: e.target.getBoundingClientRect().left}
									}));
				}
				else
				{					
					this.location.href = this.setProductsRoute(this.categories[categoryIndex].slug);
				}
			}
		},
		setPaymentProcessor: function(method)
		{
			this.paymentProcessor = method;

			Vue.nextTick(() => 
			{
				var payload = {
					processor: this.paymentProcessor,
					cart: this.cart,
					subscriptionId: this.subscriptionId,
					prepaidCreditsPackId: this.prepaidCreditsPackId,
					locale: this.locale,
					coupon: $('input[name="coupon"]').val().trim(),
					_token: $('meta[name="csrf-token"]').attr('content'),
				};

				$.post('/checkout_form', payload)
				.done(data => 
				{
					$('.form-fields').html(data.form)
				})
				.fail(() =>
				{
					console.log('Failed to get the checkout form');
				})
				.always(() =>
				{
					$('.methods .ui.dropdown').dropdown()

					$('.ui.checkbox.terms').checkbox()
				})
			})
		},
		setPrepaidCreditsPackId: function(packId)
		{
			this.prepaidCreditsPackId = packId;
		},
		checkout: function(e)
		{
			if(this.totalAmount > 0)
			{
				if(this.paymentProcessor)
				{
					e.target.disabled = true;

					if(this.paymentProcessor != 'authorizenet')
					{
						this.transactionMsg = 'processing';
					}

					var payload = {
						processor: this.paymentProcessor,
						coupon: this.couponRes.status ? this.couponRes.coupon.code : null,
						custom_amount: this.customAmount,
						tos: $('input[name="tos"]').prop('checked') || false
					};

					if(this.paymentProcessor === 'stripe' && this.paymentProcessors.stripe)
					{
						var route = this.subscriptionId !== null ? props.routes.subscriptionPayment : props.routes.payment;

						if(this.subscriptionId != null)
						{
							payload.subscription_id = this.subscriptionId;
						}
						else if(this.prepaidCreditsPackId != null)
						{
							payload.prepaid_credits_pack_id = this.prepaidCreditsPackId;
						}
						else
						{
							payload.cart = 	Base64.btoa(JSON.stringify(app.cart));
						}

						try
						{
							$.post(route, payload, null, 'json')
							.done(function(data)
							{
								if(data.hasOwnProperty('user_message'))
								{
									app.showUserMessage(data.user_message, e);

					  			return;
								}

								if(data.status)
								{
									location.href = data.redirect;

									return;
								}

								stripe.redirectToCheckout({sessionId: data.id})
								.then(function(result) 
								{
									app.showUserMessage(result.error.message, e);
								});
							})
						}
						catch(err)
						{
							app.showUserMessage(err, e);
						}
					}
					else if(this.paymentProcessor === 'payhere' && this.paymentProcessors.payhere)
					{
						var formData = decodeURIComponent($('#form-checkout').serialize()).split('&').reduce((acc, prop) => 
						{
						    prop = prop.split('=');
						    
						    acc[prop[0]] = prop[1];

								return acc;
						}, {});

						payload = Object.assign(payload, formData);

						var route = this.subscriptionId !== null ? props.routes.subscriptionPayment : props.routes.payment;

						if(this.subscriptionId == null)
						{
							payload.cart = 	Base64.btoa(JSON.stringify(app.cart));
						}
						else
						{
							payload.subscription_id = this.subscriptionId;
						}

						try
						{
					    // Called when user completed the payment. It can be a successful payment or failure
					    payhere.onCompleted = function onCompleted(orderId)
					    {
						    	var status = 'processing';
						    	var payload = {"order_id": orderId, "processor": app.paymentProcessor, "async": true};

									(function myLoop(i) {
									  setTimeout(function() 
									  {	
												$.post('/checkout/payment/order_completed', payload)
									  		.done(function(data)
									  		{
									  			status = data.status;

									  			if(status === false)
									  			{
									  				app.showUserMessage(data.user_message, e);
									  			}
									  			else if(status === true)
									  			{
									  				Vue.nextTick(function()
									  				{
									  					location.href = data.redirect_url;
									  				})
									  			}
									  		})
									  		.fail(function(data)
									  		{
									  			status = null;

									  			app.showUserMessage(data.responseJSON.message, e);
									  		})

									    if (--i && status === 'processing') myLoop(i);
									  }, 5000)
									})(5);
							}

					    // Called when user closes the payment without completing
					    payhere.onDismissed = function onDismissed() 
					    {
				  				app.transactionMsg = '';
				  				e.target.disabled = false;

					  			return;
					    };

					    // Called when error happens when initializing payment such as invalid parameters
					    payhere.onError = function onError(error)
					    {
					    		app.showUserMessage(error, e);

					  			return;
					    };

							$.post(route, payload, null, 'json')
							.done(function(data)
							{
								if(data.hasOwnProperty('user_message'))
								{
									app.showUserMessage(data.user_message, e);

					  			return;
								}

								payhere.startPayment(data.payload);
							})
							.fail(function(data)
							{
									app.showUserMessage(data.responseJSON.message, e);
							})
						}
						catch(err)
						{
								app.showUserMessage(err, e);
						}
					}
					else if(this.paymentProcessor === 'spankpay' && this.paymentProcessors.spankpay)
					{
						var route = this.subscriptionId !== null ? props.routes.subscriptionPayment : props.routes.payment;

						if(this.subscriptionId == null)
						{
							payload.cart = 	Base64.btoa(JSON.stringify(app.cart));
						}
						else
						{
							payload.subscription_id = this.subscriptionId;
						}

						try
						{
							$.post(route, payload, null, 'json')
							.done(function(data)
							{
								if(data.hasOwnProperty('user_message'))
								{
									app.showUserMessage(data.user_message, e);

					  			return;
								}

								if(data.status)
								{
									Spankpay.show({
								    apiKey: data.public_key,
								    fullscreen: false,
								    amount: data.amount,
								    currency: data.currency,
								    callbackUrl: data.callback_url,
								    onPayment: function(payment) 
								    {
										    $.post(data.return_url, {payment})
										    .done(function(data)
										    {
										    	location.href = data.redirect_url || '';
										    })	
										},
										onOpen: function()
										{

										},
										onClose: function()
										{
											app.transactionMsg = '';
						  				e.target.disabled = false;

							  			return;
										}
									})
								}
							})
						}
						catch(err)
						{
							app.showUserMessage(err, e);
						}
					}
					else if(this.paymentProcessor === 'omise' && this.paymentProcessors.omise)
					{
						OmiseCard.configure({
					    publicKey: omisePublicKey
					  });

					  payload.prepare = true

						var route = this.subscriptionId !== null ? props.routes.subscriptionPayment : props.routes.payment;

						if(this.subscriptionId == null)
						{
							payload.cart = 	Base64.btoa(JSON.stringify(app.cart));
						}
						else
						{
							payload.subscription_id = this.subscriptionId;
						}

						$.post(route, payload, null, 'json')
						.done(function(data)
						{
							if(data.hasOwnProperty('user_message'))
							{
								app.showUserMessage(data.user_message, e);

				  			return;
							}

							if(data.status)
							{
								var form = document.querySelector('#form-checkout');

								OmiseCard.open({
						      amount: data.amount,
						      currency: data.currency,
						      defaultPaymentMethod: "credit_card",
						      onCreateTokenSuccess: (nonce) => 
						      {
						          if(nonce.startsWith("tokn_")) 
						          {
						              form.omiseToken.value = nonce;
						          }
						          else
						          {
						              form.omiseSource.value = nonce;
						          }
						        	
						        	form.submit();
						      },
						      onFormClosed: () => 
						      {
								    app.transactionMsg = '';
								    e.target.disabled = false;
								  }
						    });
							}
						})
					}
					else if(this.paymentProcessor === 'authorizenet' && this.paymentProcessors.authorizenet)
					{
						window.authorizeNetResponseHandler = function(response)
						{
							if(response.messages.resultCode === "Error")
							{
								var i = 0;
								var errors = [];

								while(i < response.messages.message.length)
								{
									errors.push(`${response.messages.message[i].code} : ${response.messages.message[i].text}`);
								  i = i + 1;
								}

								app.showUserMessage(errors.join(','), e);

								e.preventDefault();
								return false;
							}
							else if(response.messages.resultCode === "Ok")
							{
								app.transactionMsg = 'processing';

								var route = app.subscriptionId !== null ? props.routes.subscriptionPayment : props.routes.payment;

								if(app.subscriptionId == null)
								{
									payload.cart = 	Base64.btoa(JSON.stringify(app.cart));
								}
								else
								{
									payload.subscription_id = app.subscriptionId;
								}

								payload = Object.assign(payload, response);

								$.post(route, payload, null, 'json')
								.done(function(data)
								{
									if(data.hasOwnProperty('user_message'))
									{
										app.showUserMessage(data.user_message, e);

						  			return;
									}

									if(data.status)
									{
										location.href = data.redirect_url;
									}
								})
							}
							else
							{
								e.target.disabled = false;
								app.transactionMsg = '';
							}
						}

						Vue.nextTick(function()
						{
							$('#AcceptUIBtn').click()
						})

						e.preventDefault();
						return false;
					}
					else if(this.paymentProcessor === 'credits' && this.paymentProcessors.credits)
					{
						var route = this.subscriptionId !== null ? props.routes.subscriptionPayment : props.routes.payment;

						if(this.subscriptionId != null)
						{
							payload.subscription_id = this.subscriptionId;
						}
						else if(this.prepaidCreditsPackId != null)
						{
							e.preventDefault();
							return false;
						}
						else
						{
							payload.cart = 	Base64.btoa(JSON.stringify(app.cart));
						}

						try
						{
							$.post(route, payload, null, 'json')
							.done(function(data)
							{
								if(data.hasOwnProperty('user_message'))
								{
									app.showUserMessage(data.user_message, e);

					  			return;
								}

								if(data.status)
								{
									$.get(data.checkout_url)
									.done(data => 
									{
										app.creditsOrder = data;

										Vue.nextTick(() =>
										{
											$('#credits-checkout-form').modal({
												closable: false,
												center: true,
											}).modal('show')
										})
									})
								}
							})
						}
						catch(err)
						{
							app.showUserMessage(err, e);
						}
					}
					else
					{
						$('#form-checkout').submit();
					}
				}
			}
			else
			{
				this.paymentProcessor = "n-a";

				Vue.nextTick(function()
				{
					$('#form-checkout').submit();
				})
			}
		},
		showUserMessage: function(message, e = null)
		{
			app.userMessage = message;

			Vue.nextTick(function()
			{
				$('#user-message').toggleClass('active', true);

				$('#user-message .close').on('click', function()
				{
					$('#user-message').toggleClass('active', false)	

					app.transactionMsg = '';

					if(e !== null)
					{
						e.target.disabled = false;
					}
				})
			});
		},
		buyNow: function(item, e)
		{
			this.addToCartAsync(item, e, () => { location.href = props.routes.checkout || '' });
		},
		groupBuyItem: function(item)
		{
			if(!this.cartHasGroupBuyItem())
			{
				$.post(props.routes.addToCartAsyncRoute, {item: item, groupBuy: 1}, null, 'json')
				.done((data) =>
				{
					if(props.allowAddToCart)
					{
						this.cart.push(data.product);
					}
					else
					{
						this.cart = [data.product];	
					}

					this.cartItems = this.cart.length

					store2.set('cart', this.cart);
					store2.set('cartItems', this.cart.length);

					location.href = props.routes.checkout
				})
			}
			
			$('#group-buy-notif').transition('bounce')
		},
		cartHasGroupBuyItem: function()
    {
    	for(let i in this.cart)
    	{
    		if(String(this.cart[i].id) === String(this.product.id) && this.cart[i].hasOwnProperty('group_buy_price'))
    		{
    			return true;
    		}
    	}

    	return false;
    },
		addToCart: function()
		{
			this.cartItems = props.allowAddToCart ? (parseInt(store2.get('cartItems', 0)))+1 : 1;

			var localStorageCart = store2.get('cart', []);

			this.cart = localStorageCart;

			if(props.allowAddToCart)
			{
				this.cart.push(this.product);
			}
			else
			{
				this.cart = [this.product];	
			}

			this.saveCartChanges();

			this.updateCartPrices();
		},
		addToCartAsync: function(item, e, callback = null)
		{
			Vue.nextTick(function()
			{
				$.post(props.routes.addToCartAsyncRoute, {item}, null, 'json')
				.done((data) =>
				{
					app.product = data.product;
					app.addToCart();

					app.$forceUpdate();

					try
					{
						Vue.nextTick(function()
						{
							callback != null ? callback() : null;
						})
					}
					catch(e){}
				})	
			})
		},
		removeFromCart: function(productId)
		{
			var indexOfProduct = 	this.getProductIndex(productId);

			this.cartItems = (this.cartItems - 1);

			this.cart.splice(indexOfProduct, 1);

			this.saveCartChanges();

			let removeFromDB = true;

			for(let i in this.cart)
			{
				if(this.cart[i].id == productId)
				{
					removeFromDB = false;
				}
			}

			if(removeFromDB)
			{
				$.post(`/remove_from_cart_async`, {id: productId});
			}
		},
		getProductIndex: function(productId, fromVueCart = true)
		{
			if(fromVueCart)
			{
				return 	this.cart.reduce(function(acc, currval) {
									return acc.concat(currval.id)
								}, []).indexOf(productId);
			}
			else
			{
				var localStorageCart = store2.get('cart', []);

				if(!localStorageCart.length)
					return -1;

				return 	localStorageCart.reduce(function(acc, currval) {
									return acc.concat(currval.id)
								}, []).indexOf(productId);
			}
		},
		saveCartChanges: function()
		{
			this.totalAmount = this.getTotalAmount();

			store2.set('cart', this.cart);
			store2.set('cartItems', this.cartItems);
		},
		updateCartItem: function(indexOfProduct)
		{
			this.cart.splice(indexOfProduct, 1, this.cart[indexOfProduct]);
		},
		applyCoupon: function(event)
		{
			var input  = event.target.previousElementSibling;
			var coupon = input.value;

			if(!coupon.length)
				return false;
			
			$.post(props.routes.coupon, 
				{
					coupon: coupon,
					for: this.subscriptionId ? 'subscription' : 'products',
					products: app.cart,
					subscription_id: this.subscriptionId
				}, 
				null, 'json')
			.done(function(res)
			{
				app.couponRes = res;

				if(res.status)
				{
					app.couponValue = Number.parseFloat(res.coupon.discount).toFixed(app.currencyDecimals);

					app.totalAmount = app.getTotalAmount();

					app.removeFromCart = function() { return false };

					app.applyCoupon = function() { return false };
				}
			})
		},
		removeCoupon: function()
		{
			location.reload();
		},
		getPaymentFee: function()
		{
			if(this.paymentFees.hasOwnProperty(this.paymentProcessor) && this.totalAmount > 0)
			{
				return Number(this.paymentFees[this.paymentProcessor]).toFixed(this.currencyDecimals);
			}

			return Number(0).toFixed(this.currencyDecimals);
		},
		getTotalAmount: function()
		{
			var paymentFee  = parseFloat(this.getPaymentFee());
			var grossAmount = 0

			if(!isNaN(this.subscriptionId) && this.subscriptionId !== null)
			{
				grossAmount += parseFloat(this.subscriptionPrice);
			}
			else
			{
				for(var item of this.cart)
				{
					grossAmount += parseFloat(item.price);
				}
			}

			if(grossAmount > 0)
			{
				var couponValue = Number.parseFloat(this.couponValue || 0).toFixed(this.currencyDecimals);
					  grossAmount = Number.parseFloat(grossAmount + (parseFloat(!isNaN(paymentFee) ? paymentFee : 0)))
					  							.toFixed(this.currencyDecimals);

				return Number.parseFloat(grossAmount - couponValue).toFixed(this.currencyDecimals);
			}

			return grossAmount;
		},
		slideScreenhots: function(slideDirection)
		{
			var screenshots 	 = this.product.screenshots;
			var screenshotsLen = this.product.screenshots.length;
			var activeIndex 	 =  screenshots.indexOf(app.activeScreenshot);

			if(slideDirection === 'next')
			{
				if((activeIndex+1) < screenshotsLen)
					this.activeScreenshot = screenshots[activeIndex+1]
				else
					this.activeScreenshot = screenshots[0]
			}
			else
			{
				if((activeIndex-1) >= 0)
					this.activeScreenshot = screenshots[activeIndex-1]
				else
					this.activeScreenshot = screenshots[screenshotsLen-1]
			}
		},
		setProductsRoute: function(categorySlug)
		{
			return `${props.routes.products}/${categorySlug}`;
		},
		setPageRoute: function(pageSlug)
		{
			return `${props.routes.pages}/${pageSlug}`;
		},
		downloadItem: function(itemId, formSelector = '#download-form')
		{
			if(!itemId)
				return;

			this.itemId = itemId;

			this.$nextTick(function()
			{
				$(formSelector).submit();
			})
		},
		downloadInvoice: function(itemId)
		{
			$.post('/invoices', {"itemId": itemId, "_token": $('meta[name="csrf-token"]').attr('content')})
			.done(data =>
			{
				window.open(data.url);
			})
		},
		downloadLicense: function(itemId, formSelector)
		{
			if(!itemId)
				return;

			this.itemId = itemId;

			this.$nextTick(function()
			{
				$(formSelector).submit();
			})
		},
		downloadFile: function(folderFileName, folderClientFileName, formSelector)
		{
			this.folderFileName = folderFileName;
			this.folderClientFileName = folderClientFileName;

			this.$nextTick(function()
			{
				$(formSelector).submit();
			})
		},
		toggleMobileMenu: function()
		{
			Vue.set(this.menu, 'mobile', Object.assign({... this.menu.mobile}, {
								type: null,
								selectedCategory: null,
								submenuItems: null,
								hidden: $('#mobile-menu').isVisible()
							}));

			$('#mobile-menu').transition('fly right', function()
			{
				$('html').toggleClass('overflow-hidden')
			});
		},
		toggleItemsMenu: function()
		{
			$('#items-menu').transition('drop');
		},
		toggleMobileSearchBar: function()
		{
			$('#mobile-search-bar').transition('drop');
		},
		collectionToggleItem: function(e, id)
		{
			if(store2.has('favorites'))
			{
				$(e.target).toggleClass('active', !$(e.target).hasClass('active'));

				var favs = store2.get('favorites');

				if(Object.keys(favs).indexOf(String(id)) >= 0)
				{
					var newFavs = Object.keys(favs).reduce((c, v) => {
													if(v != String(id))
														c[v] = favs[v];

													return c;
												}, {});

					store2.set('favorites', newFavs);

					this.favorites = newFavs;
				}
				else
				{
					$.get('/get_item_data', {id})
					.done(data =>
					{
						favs[id] = data.item;

						store2.set('favorites', favs);
					})
				}
			}
		},
		itemInCollection: function(id)
		{
			return Object.keys(this.favorites).indexOf(String(id)) >= 0;
		},
		logout: function()
		{
			$('#logout-form').submit();
		},
		setReplyTo: function(userName, commentId)
    {
      this.replyTo = {userName, commentId};

      $('#item .comment-form textarea').focus()
      .closest('.comment-form').get(0).scrollIntoView()
    },
    resetReplyTo: function()
    {
      this.replyTo = {userName: null, commentId: null};
    },
    setEditCommentId: function(commentId, e)
    {
    	this.commentId = commentId;

    	var commentBody = $(e.target).closest('.comment').find('.body').text().trim();

    	$('#item .comment-form textarea').focus().val(commentBody)
      .closest('.comment-form').get(0).scrollIntoView()
    },
    resetEditCommentId: function()
    {
    	this.commentId = null;

    	$('#item .comment-form textarea').val('')
    },
    deleteComment: function(deleteLink)
    {
    	if(confirm(this.__("Are you sure you want to delete this comment ?")))
    	{
    		location.href = deleteLink;
    	}
    },
    setEditReviewId: function(reviewId, e)
    {
    	this.reviewId = reviewId;

    	Vue.nextTick(() => {
    		var ReviewBody = $(e.target).closest('.review').find('.body .content').text().trim();
	    	var rating = $(e.target).closest('.review').find('.body .rating').data('rating');

	    	$('#item .review-form textarea').focus().val(ReviewBody)
	      .closest('.review-form').get(0).scrollIntoView()

	      $('#item .review-form .ui.rating')
	      .rating({
					maxRating: 5,
					onRate: function(rate)
					{
						$(this).siblings('input[name="rating"]').val(rate);
					}
				})
				.rating("set rating", rating)
    	})
    },
    resetEditReviewId: function()
    {
    	$('#item .reviewId-form textarea').val('')

    	this.reviewId = null;
    },
    deleteReview: function(deleteLink)
    {
    	if(confirm(this.__("Are you sure you want to delete your review ?")))
    	{
    		location.href = deleteLink;
    	}
    },
    toggleCouponForm: function()
    {
    	this.couponFormVisible = !this.couponFormVisible;
    },
    getFolderContent: function()
    {
    	var _this = this;

    	if(this.folderContent === null)
    	{
    		$.post(props.routes.productFolder, {"slug": this.product['slug'], "id": this.product['id']}, null, 'json')
    		.done(function(data)
    		{
    			if(data.hasOwnProperty('files'))
    			{
    				_this.folderContent = data.files;
    			}
    		})
    	}
    },
    getFolderFileIcon: function(fileObj)
    {
    	var fileMimeType = fileObj.mimeType;

    	if(/(text\/plain|txt)/i.test(fileMimeType))
    	{
    		return 'file alternate outline';
    	}
    	else if(/(image\/.+|\.(png|jpg|jpeg))/i.test(fileMimeType))
    	{
    		return 'file image outline';
    	}
    	else if(/zip|rar|archive|7z/i.test(fileMimeType))
    	{
    		return 'file archive outline';
    	}
    	else
    	{
    		return 'file outline';
    	}
    },
    setLocale: function(locale)
    {
    	this.locale = locale;

    	Vue.nextTick(function()
    	{
    		$('#set-locale').submit();
    	})
    },
    applyPriceRange: function(e)
    {
    	var form 			= $(e.target).closest('.form'),
    			minPrice  = form.find('input[name="min"]').val().trim(),
    			maxPrice  = form.find('input[name="max"]').val().trim();

    	if(minPrice < 0 || maxPrice < 0 || maxPrice < minPrice || minPrice === '' || maxPrice === '')
    	{    		
    		e.preventDefault();
    		return;
    	}

    	this.parsedQueryString.price_range = `${minPrice},${maxPrice}`;

    	this.location.href = queryString.stringifyUrl({url: this.location.href, query: this.parsedQueryString});
    },
    __: function(key, params = {})
    {
    	var string = this.translation[key] || key;

    	if(Object.keys(params).length)
    	{
    		for(var k in params)
    		{
    			string = string.replace(`:${k}`, params[k]);
    		}
    	}

    	return string;
    },
    price: function(price, free = false, k = false)
    {
    	k = props.showPricesInKFormat;

    	if(!isNaN(price))
    	{
    		var currencyCode = this.userCurrency ? this.userCurrency : this.currency.symbol;
    		var price 			 = Number(price).toFixed(this.currencyDecimals);

    		price = (k && price > 1000) ? Number(price / 1000).toFixed(this.currencyDecimals)+'K' : price;

    		return this.currencyPos === 'left' ? `${currencyCode} ${price}` : `${price} ${currencyCode}`;
    	}
    	
    	return free ? this.translation['Free'] : price;
    },
    priceConverted: function(price)
    {
    	if(price > 0)
    	{
    		return Number(price * this.exchangeRate).toFixed(this.currencyDecimals);
    	}
    	
    	return 0;
    },
    updatePrice: function(items)
    {
    	return $.post('/update_price', {items: items})
			.done(function(res)
			{
				return res.items;
			});
    },
    updateCartPrices: function()
    {
    	if(Object.keys(this.currencies).length)
			{
				this.updatePrice(this.cart).then(function(data)
				{
					app.cart = data.items;
				})
			}
    },
    getGuestDownloads: function()
    {
    	if(this.guestAccessToken.length)
    	{
    		$.post('/guest/downloads', {access_token: this.guestAccessToken})
    		.done(function(data)
    		{
    			if(data.hasOwnProperty('products'))
    			{
    				if(data.products.length)
    				{
    					app.guestItems = data.products;
    					app.keycodes = data.keycodes;

    					Vue.nextTick(function()
    					{
    						$('.ui.default.dropdown').dropdown({action: 'hide'})
    					})
    				}
    				else
    				{
	    				app.showUserMessage(app.__('No items found for the given token.'));
    				}

    				window.history.pushState("", "", `/guest?token=${app.guestAccessToken}`);
    			}
    		})
    		.fail(function(data)
    		{
    			app.showUserMessage(data.responseJSON.message);
    		})
    	}
    },
    downloadKey: function(itemId, itemSlug)
    {	
    	if(Object.keys(this.keycodes).indexOf(itemId.toString()) >= 0)
    	{
    		var blob = new Blob([this.keycodes[itemId]], {type: "text/plain;charset=utf-8"});

    		saveAs(blob, `${itemSlug}.txt`);
    	}
    },
    markUsersNotifAsRead: function()
    {
			$('#users-notif').remove();

			if(this.usersNotif.length)
			{
				Cookies.set('user_notif_read', this.usersNotif, {expires: 365});
				this.usersNotifRead = this.usersNotif;
			}
    },
    loadUserNotifsAsync: function()
    {
    	Push.Permission.request(() =>
			{
				setInterval(function()
	    	{
	    		$.post('/user_notifs')
		    	.done(function(notifications)
		    	{		    		
		    		if(notifications.length)
		    		{
							for(var i = 0; i < notifications.length; i++)
							{
								setTimeout(function timer() 
								{
									var userNotifsIds = store2.get('userNotifsIds', []);

									if(userNotifsIds.indexOf(notifications[i].id) < 0)
									{
										Push.create(app.appName, {
										    body: app.__(notifications[i].text, {"product_name": notifications[i].name}),
										    icon: `/storage/${notifications[i].for == '0' ? 'covers' : 'avatars'}/${notifications[i].image}`,
										    timeout: 4000,
										    onClick: function()
										    {
										    		$.post(props.routes.notifRead, {notif_id: notifications[i].id})
												    .done(function()
												    {
												      window.location.href = `/item/${notifications[i].slug}`;
												    })
										    }
										});

										store2.set('userNotifsIds', userNotifsIds.concat(notifications[i].id));
									}
							  }, i * 5000);
							}
		    		}
		    	})
	    	}, 300000)
			})
    },
    acceptCookies: function()
    {
    	Cookies.set('cookies_accepted', true, {expires: 365});
    	this.cookiesAccepted = true;
    },
    itemHasPaidLicense: function()
    {
    	var prices = this.itemPrices || {};

			if(Object.keys(prices).length)
			{
				for(var i in prices)
				{
					if(prices[i].price != 0 || prices[i].promo_price != 0)
					{
						return true;
					}
				}
			}

			return false;
    },
    setCustomItemPrice: function(e)
    {
    	var customPrice = e.target.value.trim();

    	if(parseFloat(customPrice) >= parseFloat(this.product['minimum_price']))
    	{
    		Vue.set(this.product, 'custom_price', parseFloat(customPrice));
    	}
    },
    setPrice: function(e = null)
    { 
    	if(parseInt(this.product.for_subscription))
    	{
    		return;
    	}

    	if(e !== null)   	
    	{
    		this.licenseId = e.target.value;
    	}

    	if(isNaN(this.licenseId))
    	{
    		return;
    	}

    	Vue.nextTick(()=>
    	{
    		var itemPrice 	 = this.itemPrices[this.licenseId];
    		var defaultPriceElement = $('#item > .purchase > .ui.menu > .item .price.default');
    		var promoPriceElement = $('#item > .purchase > .ui.menu > .item .price.promo');
    		var timerElement = $('#item .time-counter');

    		Vue.set(this.product, 'license_id', this.licenseId);

    		timerElement.hide();

    		if($('#item .cart-action').hasClass('dropdown'))
  			{
  				$('#item .cart-action').dropdown({
  					action: "nothing",
  					on: "hover",
  					values: [
				      {
				        name : app.__('Add to cart'),
				        value: 'add-to-cart',
				      },
				      {
				        name : app.__('Buy now'),
				        value: 'buy-now',
				      }
				    ]
  				})
  				.dropdown('set text', app.__('Buy now'))	
  			}
  			else
  			{
  				$('#item .cart-action').text(app.__('Buy now'))
  			}

  			$('#item > .purchase > .ui.menu > .item.header').toggleClass('has-promo', itemPrice.has_promo ? true : false);

    		if(itemPrice.is_free)
    		{
    			Vue.set(this.product, 'price', 0);

    			if(itemPrice.free_time !== null)
    			{
    				if(new Date(new Date(itemPrice.free_time.to).toISOString()).getTime() > new Date().getTime())
    				{
							defaultPriceElement.text(app.__('Free'));
							timerElement.show().find('.time').attr('data-json', Base64.encode(JSON.stringify(itemPrice.free_time)));

							startPromoCounter('#item .time-counter .time', '.time-counter');
    				}
    			}
    			else
    			{
    				defaultPriceElement.text(app.__('Free'));
    			}

    			if($('#item .cart-action').hasClass('dropdown'))
    			{
    				$('#item .cart-action').dropdown({
    					on: "hover",
    					values: [
					      {
					        name : app.__('Add to cart'),
					        value: 'add-to-cart',
					      }
					    ]
    				})
    				.dropdown('set text', app.__('Download'))	
    			}
    			else
    			{
    				$('#item .cart-action').text(app.__('Download'))
    			}
    		}
    		else if(itemPrice.has_promo)
    		{
    			if(!promoPriceElement.length)
    			{
    				$('#item > .purchase > .ui.menu > .item.header').prepend('<span class="price promo"></span>')

    				promoPriceElement = $('#item > .purchase > .ui.menu > .item .price.promo');
    			}

    			Vue.set(this.product, 'price', itemPrice.price);

    			if(itemPrice.promo_time !== null)
    			{
    				if(new Date(new Date(itemPrice.promo_time.to).toISOString()).getTime() > new Date().getTime())
    				{
    					defaultPriceElement.text(this.price(itemPrice.price));

    					promoPriceElement.text(this.price(itemPrice.promo_price)).show();

							timerElement.show().find('.time').attr('data-json', Base64.encode(JSON.stringify(itemPrice.promo_time)));

							startPromoCounter('#item .time-counter .time', '.time-counter');
    				}
    			}
    			else
    			{
    				defaultPriceElement.text(this.price(itemPrice.price));

    				promoPriceElement.text(this.price(itemPrice.promo_price)).show();
    			}
    		}
    		else
    		{

    			Vue.set(this.product, 'price', itemPrice.price);

    			defaultPriceElement.text(this.price(itemPrice.price));

    			promoPriceElement.hide();
    		}
			})
    },
    sendEmailVerificationLink: function(userEmail)
    {
    	$('#main-dimmer').toggleClass('active', true);

    	$.post('/send_email_verification_link', {email: userEmail})
    	.done(function(data)
    	{
    		if(data.status)
    		{
    			app.showUserMessage(data.message);	
    		}
    	})
    	.always(function()
    	{
    		$('#main-dimmer').toggleClass('active', false);
    	})
    },
    removeRecentViewedItem: function(key)
	  {
	  	if(Object.keys(this.recentlyViewedItems).indexOf(key.toString()) >= 0)
	  	{
	  		var recentlyViewedItems = {};

	  		for(var k of Object.keys(this.recentlyViewedItems))
	  		{
	  			if(k != key)
	  			{
	  				recentlyViewedItems[k] = this.recentlyViewedItems[k];
	  			}
	  		}

	  		this.recentlyViewedItems = recentlyViewedItems;

	  		store2.set('recentlyViewedItems', this.recentlyViewedItems);
	  	}
	  },
	  refreshTopPanelCover: function(name = 'homepage')
	  {
	  	$.get(`/bricks_mask?refresh=1&name=${name}`)
	  	.done(() =>
	  	{
	  		$('#top-panel .cover').css('background-image', `url('/storage/images/${name}_bricks_cover.svg?t=${new Date().getTime()}')`);
	  	})
	  },
	  getLiveSale: function()
	  {
	  	if(props.recentPurchases.enabled == '1')
	  	{
	  		var route = /home\..+/i.test(this.route) ? this.route.split('.')[1] : 'home';

				if(!Object.keys(props.recentPurchases.pages).length || props.recentPurchases.pages.hasOwnProperty(route))
				{
					var payload = {
						_token: $('meta[name="csrf-token"]').attr('content'),
					};

					$.post(`${location.origin}/live_sales`, payload)
					.done(data => 
					{
						if(data.status)
						{
							this.recentPurchase = data.sale;

							Vue.nextTick(() => 
							{
								$('#recent-purchase-popup').transition('slide right').transition({
									interval: 5000,
									duration: 1000
								})
							})
						}
					})
				}
	  	}
	  },
	},
	watch: {
	},
	created: function()
	{
		try
		{
			this.deviceIsMobile = navigator.userAgentData.mobile || false;
		}
		catch(e){}

		if(!this.transactionMsg.length)
		{
			this.cart = this.cart.filter(function(item)
									{
										return item !== null
									});

			if(this.cart.length)
			{
				this.updateCartPrices();

				this.cartItems = this.cart.reduce(function(accumulator, cartItem)
				{
					return accumulator + 1;
				}, 0);
			}
		}
		else
		{
			this.cart = [];
			this.cartItems = 0;

			store2.remove('cartItems')
			store2.remove('cart');
		}

		this.parsedQueryString = queryString.parse(this.location.search);

		if(Object.keys(this.product).length)
		{
			this.licenseId = this.product.license_id;

			this.setPrice();
		}

		if(!Cookies.get('cookies_accepted'))
		{
			this.cookiesAccepted = false;
		}
	},
	mounted: function()
	{
		this.route = document.querySelector('meta[name="route"]').getAttribute('content').trim();

		if(this.subscriptionId == null)
		{
			if(this.cartItems)
			{
				this.totalAmount = Number.parseFloat(this.cart.reduce(function(c, v){
															return c + v.price;
														}, 0)).toFixed(this.currencyDecimals);
			}	
		}
		else
		{
			this.totalAmount = Number.parseFloat(this.subscriptionPrice).toFixed(this.currencyDecimals);
		}
		

		if(!store2.has('favorites'))
		{
			store2.set('favorites', {});
		}

		this.favorites = store2.get('favorites', {});


		if(!store2.has('recentlyViewedItems'))
		{
			store2.set('recentlyViewedItems', {});
		}

		this.recentlyViewedItems = store2.get('recentlyViewedItems');

		if(this.route === 'home.product')
		{
			Vue.set(this.product, 'lastView', new Date().getTime()+`-${this.product.id}`);

			var ids = Object.keys(this.recentlyViewedItems).map(id => { return id.split('-')[1] });

			if(ids.indexOf(this.product.lastView.split('-')[1]) < 0)
			{
				var recentlyViewedItems = this.recentlyViewedItems;

				if(Object.keys(recentlyViewedItems).length === 13)
				{
					recentlyViewedItems = Object.fromEntries(Object.entries(recentlyViewedItems).slice(1))
				}

				Vue.set(this.recentlyViewedItems, this.product.lastView, this.product);
			}

			store2.set('recentlyViewedItems', this.recentlyViewedItems);
		}

		if(Cookies.get('user_notif_read'))
		{
			this.usersNotifRead = Cookies.get('user_notif_read');
		}

		try
		{
			if(props.recentPurchases.enabled == '1')
			{
				var i = Math.floor(Math.random() * (parseInt(props.recentPurchases.interval.max) - parseInt(props.recentPurchases.interval.min) + 1)) + parseInt(props.recentPurchases.interval.min);

				(async () => 
				{
					store2.set('liveSaleHasOne', new Date().getTime())

					while(true)
					{
						await sleep(i*1000);

						this.getLiveSale();
					}
				})()
			}

			var realtimeViews = JSON.parse(Base64.decode(props.realtimeViews))

			if(realtimeViews.product.enabled !== '0')
			{
				var r = realtimeViews.product.range.split(',');

				if(r.length)
				{
					Vue.set(this.realtimeViews, "product", Math.floor(Math.random() * parseInt(r[1])) + parseInt(r[0]))
				}
			}

			if(realtimeViews.website.enabled !== '0')
			{
				var r = realtimeViews.website.range.split(',');

				if(r.length)
				{
					Vue.set(this.realtimeViews, "website", Math.floor(Math.random() * parseInt(r[1])) + parseInt(r[0]))
				}
			}
		}
		catch(err){}

		this.loadUserNotifsAsync();
	}
});


$('.player').each(function()
{
	var type 	 = $(this).data('type');
	var player = $(this);

	$(type, this).on('loadeddata', function(e)
	{	
		player.attr('data-ready', true);
		player.attr('data-duration', $(this)[0].duration)

		var currentTime = $('#item .stream-player .controls .current-time');

		if(currentTime.length)
		{
			currentTime.text($(this)[0].duration.formatSeconds())
		}
	})

	$(type, this).on('timeupdate', function(e)
	{
		var progress = Number(($(this)[0].currentTime / parseFloat(player.data('duration'))) * 100);

		$(this).siblings('.controls').find('.time').css({width: `${progress}%`})

		if(progress >= 100)
		{
			$(this).closest('.player').find('.play.playing').click();		
		}
	})

	$(type, this).on('seeking', function(e)
	{
		$(this).closest('.image').find('.dimmer').addClass('active')
		$(this).closest(`.${type}-player`).find('.dimmer').addClass('active')
	})

	$(type, this).on('seeked', function(e)
	{
		$(this).closest('.image').find('.dimmer').removeClass('active')
		$(this).closest(`.${type}-player`).find('.dimmer').removeClass('active')
	})
})


$(`#item .stream-player .video`).on('wheel', function(e)
{
	var vid = $(this).siblings('video')[0];

	if(event.deltaY < 0)
	{
		if((vid.volume + 0.1) <= 1)
			vid.volume += 0.1;
	}
	else
	{
		if((vid.volume - 0.1) >= 0)
			vid.volume -= 0.1;
	}

	e.preventDefault()
})


$(`#item .stream-player video`).on('volumechange', (e)=>
{
	var volume = Math.floor($(e.target)[0].volume * 100);

	$(e.target).closest('.stream-player').find('.volume div > span span').css({height: `${volume}%`})
})


$('#item .stream-player .volume div > span')
.on('mousedown', function(e) 
{
	var progressPecent = 1 - ((e.pageY - $(this).offset().top) / $(this).height());
			progressPecent = Number(progressPecent).toFixed(2);

	$(this).closest('.stream-player').find('video')[0].volume = progressPecent > 1 ? 1 : progressPecent;

	$(this).toggleClass('mousedown', true);
})
.on('mousemove', function(e)
{
	if($(this).hasClass('mousedown'))
	{
		var progressPecent = 1 - ((e.pageY - $(this).offset().top) / $(this).height());
				progressPecent = Number(progressPecent).toFixed(2);

		$(this).closest('.stream-player').find('video')[0].volume = progressPecent > 1 ? 1 : progressPecent;
	}
})
.on('mouseup', function(e)
{
	$(this).toggleClass('mousedown', false);
})


$('#item .stream-player .maximize').on('click', function()
{
	if(document.fullscreenElement == null)
	{
		document.querySelector('#item .stream-player').requestFullscreen()
	}
	else
	{
		document.exitFullscreen()	
	}
})

$('#item .stream-player .video').on('click', function()
{
	var vid = $(this).siblings('video')[0];

	if(vid.paused)
		vid.play()
	else
		vid.pause()
})


$('#item .stream-player .video').on('dblclick', function()
{
	if(document.fullscreenElement == null)
	{
		document.querySelector('#item .stream-player').requestFullscreen()
	}
	else
	{
		document.exitFullscreen()	
	}
})


$(document).on('click', `.player .play`, function()
{
	try
	{
		if($(this).closest('.player').data('ready') == false)
			return;

		var type = $(this).closest('.player').data('type');

		$(this).closest('.play').addClass('playing');

		$(this).closest('.player').removeClass('stopped').find(type)[0].play();

		$(this).siblings('.wave').prop('mousedown', false);
	}
	catch(e){}
})


$(document).on('click', `.player .play.playing`, function()
{
	try
	{
		if($(this).closest('.player').data('ready') == false)
			return;

		var type = $(this).closest('.player').data('type');

		$(this).closest('.play').removeClass('playing');

		$(this).closest('.player').find(type)[0].pause();

		$(this).siblings('.wave').prop('mousedown', false);
	}
	catch(e){}
})


$(document).on('click', `.player .stop`, function()
{
	try 
	{
		if($(this).closest('.player').data('ready') == false)
			return;

		$(this).siblings('.play').removeClass('playing')

		var player = $(this).closest('.player');
		var type 	 = player.data('type');
		var track  = player.find(type)[0];

		player.addClass('stopped');

		track.currentTime = 0;
		track.pause();

		$(this).siblings('.wave').prop('mousedown', false);
	}
	catch(e){}
})


$(document).on('dblclick', `.player video`, function()
{
	try 
	{
		if($(this).closest('.player').data('ready') == false)
			return;

		var video = $(this)[0];

		if(video.requestFullscreen) 
		{
		  video.requestFullscreen();
		} 
		else if(video.mozRequestFullScreen) 
		{
		  video.mozRequestFullScreen();
		} 
		else if(video.webkitRequestFullscreen) 
		{
		  video.webkitRequestFullscreen();
		} 
		else if(video.msRequestFullscreen) 
		{ 
		  video.msRequestFullscreen();
		}
	}
	catch(e){}
})



$('.player .wave')
.on('mouseup', function() 
{
	$(this).prop('mousedown', false);
})
.on('mousedown', function(e) 
{
	var player = $(this).closest('.player');

	if(player.data('ready') == false)
		return;

	var progressPecent = (e.pageX - $(this).offset().left) / $(this).width();

	player.find(player.data('type'))[0].currentTime = Math.floor(Math.floor(player.data('duration')) * progressPecent);
	
	$(this).prop('mousedown', true);
})
.on('mousemove', function(e)
{
	var player = $(this).closest('.player');

	if($(this).prop('mousedown'))
	{
		var progressPecent = (e.pageX - $(this).offset().left) / $(this).width();

		player.find(player.data('type'))[0].currentTime = Math.floor(Math.floor(player.data('duration')) * progressPecent);
	}
})



$(()=>
{
	new Carousel("#item .l-side .row.item .card .content.images .ui.items", 1.5, false)

	$(document).on('click', '#user .purchases tr.parent .links a', function()
	{
		var products = $(this).closest('tr').next('tr.products');

		products.toggleClass('active', !products.hasClass('active'));
	})

	if(store2.get('cookieAccepted') !== true)
  {
      setTimeout(() =>
      {
          $('#cookie').transition({
            animation : 'slide up',
            duration  : 500
          });
      }, 3000)

      $('#cookie .button.accept').on('click', function()
      {
        store2.set('cookieAccepted', true)
        
        $('#cookie').transition({
          animation : 'fade',
          duration  : 800
        });     
      })
  }

	$(window).on('click', function(e)
	{
		if(!$(e.target).closest('#top-menu .dropdown.cart').length)
		{
			$('#top-menu .dropdown.cart .menu').replaceClass("visible", "hidden");
		}

		if(!$(e.target).closest('#top-menu .dropdown.notifications').length)
		{
			$('#top-menu .dropdown.notifications .menu').replaceClass("visible", "hidden");
		}

		if(!$(e.target).closest('#top-menu .dropdown.search').length)
		{
			$('#top-menu .dropdown.search .menu').replaceClass("visible", "hidden");
		}

		if(!$(e.target).closest('.search-form').length)
		{
			app.searchResults = [];
		}

		if(!$(e.target).closest('#live_search').length)
		{
			app.liveSearchItems = [];
		}
	})


	$(document).on('click', '#item .item[data-value="add-to-cart"]', function()
	{
		app.addToCartAsync(app.product);
	})

	$(document).on('click', '#item .item[data-value="buy-now"]', function()
	{
		app.buyNow(app.product);
	})


	$(document).on('click', '#body .card.product .item-action', function()
	{
		$(this).transition({
	    animation : 'tada',
	    duration  : 1000,
	    interval  : 200
	  })

		var item = JSON.parse(Base64.decode($(this).data('item')));

		if($(this).hasClass('add-to-cart'))
		{
			app.addToCartAsync(item)
		}
		else if($(this).hasClass('buy-now'))
		{
			app.buyNow(item)
		}
	})


	$(document).on('click', '#top-menu .dropdown.notifications .item:not(.all), #user .notifications .items a.item', 
  function()
  {
    var notifId = $(this).data('id');
    var _href = $(this).data('href');
    
    if(isNaN(parseInt(notifId)))
      return;

    $.post(props.routes.notifRead, {notif_id: notifId})
    .done(function()
    {
      location.href = _href;
    })
    .always(function()
    {
      if(location.href.includes('#'))
        location.reload()
    })
  })



  $(document).on({
  	mouseenter: function(e)
  	{
  		Vue.nextTick(function()
  		{
		  	if(app.previewIsPlaying || app.previewTrack == null) return;

	  		app.previewTrack.play();
	  
	  		app.previewIsPlaying = true;
  		})
  	},
  	mouseleave: function(e) 
  	{
  		Vue.nextTick(function()
  		{
	  		if(app.previewTrack == null) return;

	  		app.previewTrack.pause();

		  	app.previewTrack.currentTime = 0;

		  	app.previewIsPlaying = false;	
  		})
    }
  }, '.newest-item.popup.audio .image .play a');


  $(document).on('click', '.like .reactions a', function()
  {
  	var $this     = $(this);
  	var reaction  = $(this).data('reaction');
  	var groups  	= location.href.match(/.+\/item\/(?<id>\d+)\/.+/).groups;
  	var item_id   =  $(this).closest('.reactions').data('item_id');
  	var item_type = $(this).closest('.reactions').data('item_type');

  	$.post('/save_reaction', {reaction, product_id: groups.id || null, item_id, item_type})
  	.done(function(res)
  	{
  		if(res.status)
  		{
  			var reactionsHtml = [];

  			for(var i in res.reactions)
  			{
  				reactionsHtml.push('<span class="reaction" data-reaction="' + i + '" data-tooltip="' + res.reactions[i] + '" data-inverted="" style="background-image: url(\'/assets/images/reactions/' + i + '.png\')"></span>');
  			}  			

  			var savedReactions = $this.closest('.main-item').find('.extra').find('.saved-reactions');
  			var commentsCount = $this.closest('.main-item').find('.extra').find('.count');

  			if(!savedReactions.length)
  			{
  				if(commentsCount.length)
  				{
  					$this.closest('.main-item').find('.extra').html('<div class="saved-reactions" data-item_id="' + item_id + '" data-item_type="' + item_type + '"></div><div class="count">' + commentsCount.html() + '</div>')
  				}
  				else
  				{
  					$this.closest('.main-item')
  					.append('<div class="extra"><div class="saved-reactions" data-item_id="' + item_id + '" data-item_type="' + item_type + '"></div>')	
  				}  				
  				
  				$this.closest('.main-item').find('.saved-reactions').html(reactionsHtml.join(''));
  			}
  			else
  			{
  				savedReactions.html(reactionsHtml.join(''));
  			}
  			
  		}
  	})
  })

  $(document).on('click', '.saved-reactions .reaction', function()
  {
  	var reaction  = $(this).data('reaction');
  	var groups  	= location.href.match(/.+\/item\/(?<id>\d+)\/.+/).groups;
  	var item_id   =  $(this).closest('.saved-reactions').data('item_id');
  	var item_type = $(this).closest('.saved-reactions').data('item_type');

  	$.post('/get_reactions', {reaction, product_id: groups.id || null, item_id, item_type, users: true})
  	.done(function(res)
  	{
  		if(res)
  		{
  			app.usersReactions = res.reactions;
  			app.usersReaction = reaction;

  			Vue.nextTick(function()
  			{
  				$('#reactions').modal('show');
  			})
  		}
  	})
  })


  $(document).on('click', '#reactions .header a', function()
  {
  	app.usersReaction = $(this).data('reaction');
  })


	$('#top-menu .dropdown.cart.toggler').on('click', function()
	{
		$('#top-menu .dropdown.cart .menu').transition('drop');
	})

	$('#top-menu .dropdown.notifications.toggler').on('click', function()
	{
		$('#top-menu .dropdown.notifications .menu').transition('drop');
	})

	$('#items .column.left .tags label').on('click', function()
	{
		location.href = $(this).closest('a')[0].href;
	})

	$('#top-menu .dropdown.search i.toggler').on('click', function()
	{
		$('#top-menu .dropdown.search .menu').transition('drop');
	})

	$('.close.icon').on('click', function()
	{
		$(this).closest('.ui.modal').modal('hide');
	})


	$('#user-downloads tbody .image')
	.popup({
		inline     : true,
		hoverable  : true,
		position   : 'bottom left'
	})


	setTimeout(function()
	{
		$('#users-notif').css('visibility', 'visible');
	}, 100);


	$('.search.link').on('click', function()
	{
		if($(this).siblings('input[name="q"]').val().trim().length)
		{
			$(this).closest('form').submit();
		}
	})

	$('.search.dropdown form').on('submit', function(e)
	{
		if(!$(this).find('input[name="q"]').val().trim().length)
		{
			e.preventDefault();
			return false;
		}
	})

	$('.search-form').on('submit', function(e)
	{
		if(!$(this).find('input[name="q"]').val().trim().length)
		{
			e.preventDefault();
			return false;
		}
	})

	$('form.newsletter .plane.link').on('click', function()
	{
		$(this).closest('.form.newsletter').submit();
	})

	$('.form.newsletter').on('submit', function(e)
	{
		if(!/^(.+)@(.+)\.([a-z]+)$/.test($(this).find('input[name="email"]').val().trim()))
		{
			e.preventDefault();
			return false;
		}
	})

	

	$('.screenshot').on('click', function()
	{
		app.activeScreenshot = $(this).data('src');

		$('#screenshots').modal('show');
	})

	$(document).on('click', '.logout', function() {
		$('#logout-form').submit();
	})

	$('#item-r-side-toggler').on('click', function()
	{
		$('#item .r-side').transition('fly right');
	})


	$('#item .l-side .top.menu a.item:not(#item-r-side-toggler a)')
	.on('click', function()
	{
		$('#item .l-side .top.menu a.item').removeClass('active');
		$(this).toggleClass('active', true);

		$('#item .l-side .item > .column').hide()
		.siblings('.column.' + $(this).data('tab')).show();
	})


	$('.left-column-toggler').on('click', function()
	{
		$('#items .left.column').transition({
			animation: 'slide right'
		})
	})


	$('#mobile-menu .categories .items-wrapper').on('click', function()
	{
		$(this).toggleClass('active')
					.siblings('.items-wrapper').removeClass('active');
	})


	$('#items-menu>.item').on('click', function()
	{	
		$(this).toggleClass('active')
					.siblings('.item')
					.removeClass('active');
	})



	$('#item .card .header .link.angle.icon').on('click', function()
	{
		$(this).closest('.card').find('.content.body').toggle();
	})



	$(window).click(function(e)
	{
		if(!$(e.target).closest('#items-menu').length || $(e.target).closest('.search-item').length)
		{
			$('#items-menu>.item').removeClass('active');
		}
	})


	$('#user-profile .menu.unstackable .item').on('click', function()
	{
		var tab = $(this).data('tab');

		$(this).toggleClass('active', true)
					.siblings('.item').removeClass('active');
					
		$('#user-profile table.'+tab)
		.show()
		.siblings('.table').hide();
	})



	$('#user-profile input[name="user_avatar"]').on('change', function() {
		var file    = $(this)[0].files[0];
		var reader  = new FileReader();

		if(/^image\/(jpeg|jpg|ico|png|svg)$/.test(file.type))
		{
			reader.addEventListener("load", function() {
				$('#user-profile .user_avatar img').attr('src', reader.result);
			}, false);

			if(file)
			{
				reader.readAsDataURL(file);

				try
				{
					$('input[name="user_avatar_changed"]').prop('checked', true);
				}
				catch(err){}
			}
		}
		else
		{
			alert(app.__('File type not allowed!'));

			$(this).val('');
		}
	})


	$('.ui.checkbox').checkbox();
	$('.ui.checkbox.checked').checkbox('check');
	
	$('.ui.dropdown').dropdown();
	$('.ui.default.dropdown').dropdown({action: 'hide'})
	$('.ui.dropdown.nothing').dropdown({action: 'nothing', on: 'hover'});

	$('.ui.dropdown.like').dropdown({on: 'hover'});

	$('.ui.rating.active').rating({
		onRate: function(rate)
		{
			$(this).siblings('input[name="rating"]').val(rate);
		}
	});

	$('.ui.rating.disabled').rating('disable');

	$('#recently-viewed-items .items').scrollLeft(-9999)
	
	if($('meta[name="route"]').attr('content').trim() === 'home.product')
	{
		if(location.href.indexOf('#') >= 0)
		{
			var tab = $(`#item .l-side .top.menu .item[data-tab="${location.href.split('#')[1]}"]`);

			if(tab.length)
				tab[0].click();
		}

		if(app.canShare && app.deviceIsMobile)
		{
			$(document).on('click', '#share-api', function()
			{
				try
				{
					navigator.share({
						"title": app.product.name,
						"url": location.href,
						"text": app.product.name
					})
				}
				catch(e){}
			})
		}

		if($('#item > .purchase').length)
		{
			const purchaseBarOffsetTop =  $('#item > .purchase').offset().top;

			$(window).on('scroll', function()
			{
				if(window.pageYOffset >= purchaseBarOffsetTop) 
				{
			    $('#item > .purchase').addClass("fixed")
			  }
			  else
			  {
			    $('#item > .purchase').removeClass("fixed");
			  }
			})
		}
	}
	

	$('#support .segments .segment').on('click', function()
	{
		$(this).toggleClass('active').siblings('.segment').toggleClass('active', false)
	})

	$('.message .close')
	.on('click', function() {
		$(this)
			.closest('.message')
			.transition('fade')
		;
	})


	$('#items .column.left .filter.cities .ui.dropdown input[type="hidden"]').on('change', debounce(function()
	{
			app.parsedQueryString.cities = $(this).val();
			
			Vue.nextTick(function()
			{
				location.href = queryString.stringifyUrl({url: app.location.href, query: app.parsedQueryString});
			})
	}, 3000))
	

	$('#user .profile input[name="cashout_method"]').on('change', function()
	{
		var value = $(this).val().trim();
		
		if(!/^paypal_account|bank_account$/i.test(value))
		{
			return;
		}

		$(`#user .profile .option.${value}`).toggleClass('d-none', false)
		.siblings('.option').toggleClass('d-none', true)
	})


	$('#live-search input').on('keyup', debounce(function()
	{
			var q = $(this).val().trim();

			if(q.length)
			{
				$.post('/items/live_search', {q})
				.done(function(data)
				{
					app.liveSearchItems = data.products;
				})
			}
	}, 500))


	$(document).on('click', '#featured-items .ui.menu .item:not(.active)', function()
	{
		var category = $(this).data('category');

		$(this).toggleClass('active', true).siblings('.item').toggleClass('active', false);

		$(`#featured-items .cards.${category}`).toggleClass('active', true)
		.siblings('.cards').toggleClass('active', false);

		$('#featured-items .more-items').attr('href', `/items/category/${category}`);
	})


	$('.product.card.video .video, #item .container .left-side > .header > div.image .video').on('click', function()
  {
  	var src = $(this).data('src');

  	if(src.length)
  	{
  		$('.video-player').modal({
  			center: true
  		})
  		.modal('show')
  		.find('video').attr('src', src);
  	}
  })
	


	if(window.isMasonry)
	{
		$('.ui.cards.is_masonry').each(function()
		{
			$('.card', this).wrap('<div class="masonry-item">');

			resizeAllGridItems(this);

			if($('video', this).length)
			{
				var vidsCount  = $('video', this).length;
				var tries      = 5;

				var videosLoaded = setInterval(()=>
				{
					var loadedVids = 0;

					for(k = 0; k < vidsCount; k++)
					{
						loadedVids += $('video', this)[k].readyState === 4 ? 1 : 0;
					}

					if(loadedVids === vidsCount || tries === 0) 
					{						
						resizeAllGridItems(this);
						clearInterval(videosLoaded);
					}

					tries -= 1;
				}, 200)
			}

			$(window).resize(()=>
			{
				resizeAllGridItems(this);
			})
		})
	}


	if(app.route == 'home.product')
	{
		$('#item .ui.rating').rating({
			maxRating: 5,
			onRate: function(rate)
			{
				$(this).siblings('input[name="rating"]').val(rate);
			}
		})

		var tab = queryString.parseUrl(location.href).query.tab;

		if(tab != undefined)
		{
			$(`#item .container .left-side > .body > .ui.menu > .item[data-tab="${tab}"]`).click()
		}

		var itemVideo = $('#item .stream-player video');

		if(itemVideo.length)
		{
			$(itemVideo).on('timeupdate', function(e)
			{
				var currentTime = $(this)[0].currentTime.formatSeconds()
													.split(':').reduce((acc, time) => (60 * acc) + +time)
													.formatSeconds()

				$(itemVideo).siblings('.controls').find('.current-time').text(currentTime)
			})

			document.addEventListener('fullscreenchange', function()
			{
				if(document.fullscreenElement === null)
				{
					$('#item .stream-player .maximize').removeClass('is-full')
				}
			});
		}

		if(app.parsedQueryString.tab === 'files')
		{
			app.getFolderContent();
		}
	}


	$('#user input[name="affiliate_earnings_date"]').on('change', function()
	{
		$.get('/user_affiliate_earnings', {"refresh": true, "date": $(this).val().trim()})
		.done(data =>
		{
			$("#user .credits .affiliate-earnings > .grid").html(data.html)	
		})
	})


	setTimeout(() =>
	{
		$.get('/update_statistics');

		try
		{
			var realtimeViews = JSON.parse(Base64.decode(props.realtimeViews))

			if(realtimeViews.product.enabled !== '0' || realtimeViews.website.enabled !== '0')
			{
				function getRealtimeViews()
				{
					var itemId = app.route === 'home.product' ? `${app.product.id}` : null;

					$('#realtime-views').html(`<script type="application/javascript" src="/realtime_views?t=${new Date().getTime()}&i=${itemId}"></script>`);
				}

				if($('#realtime-views').length)
				{
					getRealtimeViews();

					setInterval(() => 
					{
						getRealtimeViews();
					}, (realtimeViews.refresh || 5) * 1000)
				}
			}
		}
		catch(err){}
		
		
	}, 5000)



	$('[vhidden]').removeAttr('vhidden');
})
