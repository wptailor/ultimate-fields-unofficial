(function($, document, window) {
	var media, UF, Field, Shortcode;

	window.UF = UF = {
		// Save a reference for the window object
		window: $( window ),

		// Strings that might get overwritten for non-english sites
		Strings: {},

		// Tranuform every word to uppercase
		ucwords: function( string ) {
			return (string + '').replace(/^([a-z])|[\s_]+([a-z])/g, function ($1) {
				return $1.toUpperCase();
			})
		},

		// Check if the argument is set
		isset: function(item) {
			return typeof(item) != 'undefined';
		},

		// Get the escaped format of a value
		escape: function( text ) {
			var escaped = $( '<div />' ).text( text ).html();
			return escaped;
		},

		// Main field class
		Field: function( $item ) {
			/**
			 * Call the construct method.
			 * The construct method should be called in each sub-class
			 * After the construct method is done, initialize() will be called
			 */
			this.construct( $item );
		},

		// Main container class
		Container: function( $container ) {
			/**
			 * Call the construct method. Similar to Field above
			 */
			this.constructContainer( $container );
		},

		// Intialize options panels
		initOptions: function() {
			// Init options pages
			$( '.uf-options' ).each(function() {

				new UF.ContainerOptions( $( this ) );
			});
		},

		// Initialize postmeta containers
		initPostmeta: function() {
			var id;

			if( typeof( UF_Postmeta ) != 'undefined' ) {
				for( id in UF_Postmeta ) {
					$( '.uf-borderless-box, .postbox' ).filter( '#' + id ).each(function() {
						new UF.ContainerPostMeta( id, UF_Postmeta[ id ] );
					});
				}
			}

			UF.ContainerPostMeta.listen();
		},

		// Initiator - detect containers and start
		init: function() {
			// Allow additional fields
			$( document ).trigger( 'uf_extend', UF );
			
			// Add dependencies if available
			if( typeof( UF_Dependencies ) != 'undefined' ) {
				UF.addDependencies( UF_Dependencies );
			}

			UF.initOptions();
			UF.initPostmeta();
		},

		// Hold the height of the admin bar. Always visible, no need to check
		adminBarHeight: 28,

		/* Dependencies holder. Format:
		{
			containerId: {
				 fieldId: {
				 	relation: 'AND|OR',
				 	targets: {
				 		fieldId: {
				 			value: '',
				 			compare: '=|==|>=|<=|<|>|!=|NOT_NULL|NULL|IN|NOT_IN'
				 		},
				 		fieldId: {...}
				 	}
			 	},
			 	fieldId: { ... },
			 	fieldId__inner: { // For repeaters
			 		same-as-container
			 	}
			},
			containerId: { ... }
		}
		*/
		dependencies: {},

		// Since this script should be in the header, use this within the HTML
		addDependencies: function( deps ) {
			$.extend( this.dependencies, deps );
		}
	};

	// Enable better compression + decrease code size a bit
	Field = UF.Field;

	// Init UF on document ready
	$( document ).ready( UF.init );

	/**
	 * When needed, creates a new media uploader popup
	 */
	UF.Media = media = {
		// The callback that will be called when a file is selected
		callback: function( attachment ) {},

		/**
		 * Default settings for the popup
		 */
		defaults: {
			// The type of needed files. Can be all/image/video/audio.
			type: 'all',

			// The ID of the selected file. Can be empty
			selected: null,

			// The title of the whole popup
			title: UF.Strings.selectMedia,

			// The text of the button.
			buttonText: UF.Strings.saveAndUse,

			// Enables multiple items
			multiple: false
		},

		/**
		 * Similar to the defaults, holds the current options
		 */
		options: {},

		/**
		 * The active frame.
		 *
		 * A frame will be created upon each file request and after selecting/closing
		 * that frame will be destroyed. This way, multiple uploads can request different
		 * settings and not get messed up.
		 */
		_frame: null,

		/**
		 * Opens a window with particular settings.
		 *
		 * The options object should either be empty or follow the defaults from above
		 */
		requestFile: function( options, callback ) {
			var args, frame;

			media.options = options = $.extend( media.defaults, options ? options : {} );
			media.callback = callback;

			// Prepare the args for the dialog
			args = {
				title: options.title,
				button: {
					text: options.buttonText
				},
				multiple: options.multiple
			};

			if( options.type != 'all' ) {
				args.library = {
					type: options.type
				}
			}

			// Creat the frame
			frame = media.frame = wp.media( args );

			// Modify it once open
			frame.on( 'ready', media.modifyPopup );

			// Add a local callback
			frame.state( 'library' ).on( 'select', media.onSelect );

			// Pre-select file if needed
			if( options.selected ) {
				frame.on( 'open', media.selectFile );
			}

			// Open the frame
			frame.open();
		},

		/**
		 * Opens a window with particular settings and the ability to select multiple images.
		 *
		 * Uses the UF.Gallery media frame.
		 */
		requestGallery: function( options, callback ) {
			var args, frame;

			media.options = options = $.extend( {}, options ? options : {} );
			media.callback = callback;
			media.options.multiple = true;

			// Prepare the args for the dialog
			args = {
				button: {
					text: 'blabla'//options.buttonText
				}
			};

			if( options.type != 'all' ) {
				args.library = {
					type: options.type
				}
			}

			// Create the frame and set it's callback
			media.frame = frame = new UF.Gallery( args );
			frame.on( 'update', callback );

			if( typeof( options.selected ) != 'undefined' ) {
				frame.setState('gallery-edit');
				media.selectFiles();
			}

			frame.open();
		},

		/**
		 * This is triggered once the popup is being closed and has files to send.
		 *
		 * It will either sent the first selection to onSelect or call it once
		 * for each file that has been selected in the popup.
		 */
		onSelect: function() {
			if( media.options.multiple ) {
				// Call onSelect once for each image
				this.get( 'selection' ).map( media.attachment );
			} else {
				// Only use the first item
				media.attachment( this.get( 'selection' ).first() );
			}

			// Destroy the frame
			delete media.frame;
		},

		// Handles selecting of items
		attachment: function( attachment ) {
			// Actually call the callback
			media.callback( attachment );
		},

		// Modifies the already rendered popup
		modifyPopup: function() {
			// Only hide the sidebar
			$( '.media-modal' ).addClass( 'no-sidebar' );
		},

		// Selects an existing file by ID
		selectFile: function() {
			var selection = media.frame.state().get( 'selection' );			
			attachment = wp.media.attachment( media.options.selected );
			attachment.fetch();
			selection.add( attachment ? [ attachment ] : [] );
		},

		// Selects multiple files by ID
		selectFiles: function() {
			var library = UF.Media.frame.state().get( 'library' );
			for( i in media.options.selected ) {
				attachment = wp.media.attachment( media.options.selected[ i ] );
				attachment.fetch();
				library.add( attachment ? [ attachment ] : [] );
			}
		}
	}

	/**
	* Adding static stuff to the base field class
	*/
	_.extend( Field, {
		// An array that will contain most fields
		fields: [],

		// Add field proxy, might be used for dependencies, etc.
		addField: function( field ) {
			this.fields.push( field );
		},

		// Factory-like method
		initField: function( node ) {
			var $node = $( node ), theClass, words, field;

			// Get class words
			words = node.className.replace( /^.*uf-field-([^ ]+).*$/i, '$1' ).split( '-' );
			
			// Join uppercase words to form the class
			theClass = '';
			for( i in words ) {
				theClass += UF.ucwords( words[i] );
			}

			// Check if there's a specific class for this field
			if( typeof( Field[ theClass ] ) != 'function' ) {
				console.log('Please implement ' + theClass);
				field = new Field( $node );
			} else {
				// Create the field
				field = new Field[ theClass ]( $node );
			}

			// Push it to the globally available fields
			this.addField( field );

			return field;
		},

		// Add a specific field class
		extend: function( type, proto, staticProps ) {
			var base = this, i, type;

			// There could be multiple types for a single field
			types = type.split(',');

			for( i in types ) {
				type = types [ i ];

				// Create the class
				Field[ type ] = function( $item ) {
					this.construct( $item );
				}

				// Inherit base
				_.extend( Field[ type ].prototype, base.prototype );

				// Add new prototype methods and properties
				_.extend( Field[ type ].prototype, proto );

				// Add static methods and properties
				if( UF.isset( staticProps ) ) {
					_.extend( Field[ type ], staticProps );
				} 

				// Add the ability for the field to be inherited
				Field[ type ].extend = function( subType, subProto, subStatic ) {
					base.extend.call( Field[ type ], subType, subProto, subStatic );
				}
			}
		}
	});

	/**
	 * Add common methods and properties to the base field class
	 */
	_.extend( Field.prototype, {
		// Main constructor, called for each field
		construct: function( $item ) {
			var field = this;

			// Everybody is innocent before proving guilty :)
			this.valid = true;

			// Hold a representation of the input's value
			this.value = null;

			// External event subscribers
			this.subscribers = {};

			// Double-check to make sure the item's a jQuery object
			if( ! UF.isset( $item.size ) || ! $item.size() ) {
				return;
			}

			// Hold the ID of the field in it's container
			this.id = $item.data( 'id' );

			// The row property is linked to the .uf-field element
			this.row = $( $item );

			// The input holds either the <input /> or the corresponding div(s)
			// One row might contain multiple inputs, so make sure to work with .each()
			if( this.row.is( '.multilingual' ) ) {
				this.input = this.row.find( '.lang-input' ).children();
			} else {
				this.input = this.row.find( '.field-wrap:eq(0)' ).children();
			}

			// Get the data of the field
			this.data = this.row.data();

			// Add the 'this' handle to the DOM element for easy access
			this.row.data( 'uf', this );

			// Check if field is required and fetch the RegEx
			this.initRequired();

			// Show/hide multilinual fields and make controls work
			this.initMultilingual();

			// Add help icon helper
			this.helpIcon();

			// Listen to value changes
			this.bindChange();

			// Collect initial values
			this.triggerChange();

			// Call sub-class initialize method
			if( this.initialize ) {
				this.initialize();
			}
		},

		// Binds events for input changes. Could be inherited later, along with triggerChange
		bindChange: function() {
			var field = this;

			this.row.on( 'change', 'input,select,textarea', function() {
				var $el = $(this);
				field.setValueFromInput( $el.val(), $el );
			} );
		},

		// Trigger change to the input
		triggerChange: function() {
			this.row.find( 'input,select,textarea' ).trigger( 'change' );
		},

		// Set value, called from bindChange
		setValue: function( value, language ) {
			if( typeof( language ) != 'undefined' ) {
				// Store a multilingual value
				this.value[ language ] = value;
			} else {
				// Store a single value for language
				this.value = value;
			}

			// Trigger an event
			this.trigger( 'valueChanged', this.value );
		},

		// Set a value by detecting the input language
		setValueFromInput: function( value, $node ) {
			var language, $input;

			if( this.multilingual ) {
				// for multilingual fields, detect the language first
				$input = $node.is( '.lang-input' ) ? $node : $node.closest( '.lang-input' );
				language = $input.attr( 'class' ).replace(/^.*lang-input-(\w\w).*$/i, '$1');
				this.setValue( value, language );
			} else {
				// the field isn't multilingual, simply store the value
				this.setValue( value );
			}
		},

		// Collect required field data
		initRequired: function() {
			var modifier, expression;

			if( UF.isset( this.data.regex ) ) {
				this.isRequired = true;

				// Extract the regular expression and convert it to a JS one
				modifier   = this.data.regex.replace( /^.*\/(\w*)$/, '$1' );
				expression = this.data.regex.replace( /^\/\^?([^\$]*)\$*\/\w*$/, '^$1$' );
				this.validationRule = new RegExp( expression, modifier );
			} else {
				this.isRequired = false;
			}
		},

		// Bring the multilingual controls to life
		initMultilingual: function() {
			var field = this, $wrap, $buttons, $inputs;

			// Don't do anything on non-multilingual fields
			if( ! this.row.is( '.multilingual' ) ) {
				this.multilingual = false;
				return;
			}

			// Since the field is multilingual, we'll be storing multiple values
			this.value = {};

			// Set as multilingual
			this.multilingual = true;
			$wrap             = this.row.find( '.uf-lang-wrap:eq(0)' );
			$buttons          = $wrap.children( '.uf-lang-switch' );
			$inputs           = $wrap.children( '.lang-input' );

			// Button events
			$buttons.on( 'click', 'a', function( e ) {
				var $this = $( this ),
					lang  = $this.data( 'language' );

				e.preventDefault();

				$this
					.addClass( 'active' )
					.parent()
					.siblings()
					.children()
					.removeClass( 'active' );

				$inputs
					.hide()
					.filter( '.lang-input-' + lang )
					.show();

				// Trigger a window resize so things go in place
				$( window ).resize();

				// Save the language in case it's needed
				field.activeLanguage = lang;
			});

			// Trigger initial change
			$buttons.find( 'a' ).eq( 0 ).click();
		},

		// Add actions to the help icon
		helpIcon: function() {
			// Since we're using the default browser tooltip,
			// just prevent the page from jumping
			this.row.on( 'click', '.label .help', function() {
				return false;
			} );
		},

		// Checks if the field's value is valid for required fields
		// false - no errors
		// true - error text
		check: function() {
			var valid;

			if( ! this.isRequired ) {
				return false;
			}

			if( valid = this.checkValue() ) {
				this.row.removeClass( 'invalid' );
			} else {
				this.row.addClass( 'invalid' );
			}

			return valid !== true;
		},

		// Check the field's value - this might differ in successors
		checkValue: function() {
			var valid = true;

			if( this.multilingual ) {
				for( i in this.value ) {
					if( ! this.validationRule.test( this.value[ i ] ) ) {
						valid = false;
					}
				}
			} else {
				valid = this.validationRule.test( this.value );
			}

			return valid; 
		},

		// Add an event listener
		bind: function( eventName, callback, context ) {
			// Check if there's an array of subscribers for this event, create if missing
			if( !UF.isset( this.subscribers[ eventName ]) ) {
				this.subscribers[ eventName ] = [];
			}

			// Add the listener
			this.subscribers[ eventName ].push({
				callback: callback,
				context: context
			});
		},

		// Add the ability to remove a callback or all callbacks if using with a single parameter
		unbind: function( eventName, callback ) {
			var i;

			// Nothing to do here without subscribers
			if( !UF.isset( this.subscribers[ eventName ]) ) {
				return;
			}

			if( UF.isset( callback ) ) {
				// Delete the specific event
				clear = [];
				for( i in this.subscribers[ eventName ] ) {
					if( this.subscribers[ eventName ][ i ].callback != callback ) {
						clear.add( this.subscribers[ eventName ][ i ] );
					}
				}
				this.subscribers[ eventName ] = clear;
			} else {
				// Delete all events
				delete this.subscribers[ eventName ]
			}
		},

		// Trigger an event
		trigger: function( eventName, data ) {
			var subscriber, i;

			if( !UF.isset( this.subscribers[ eventName ] ) ) {
				return;
			}

			for( i in this.subscribers[ eventName ] ) {
				subscriber = this.subscribers[ eventName ][ i ];

				if( UF.isset( subscriber.context) ) {
					subscriber.callback.call( context );
				} else {
					subscriber.callback( data );
				}
			}
		}
	});

	/**
	 * Add common methods and properties to the base container class
	 */
	_.extend( UF.Container.prototype, {
		// Main constructor, called for each panel
		constructContainer: function( $container ) {
			// The ID of the container, used primarily for dependencies
			this.id = null;

			// The element property will contain the main DOM element
			this.element = null;

			// All fields' DOM elements
			this.$fields = null;

			// All fields' Field objects
			this.fields = {};

			// Hold all field's value for dependencies
			this.values = {};

			// Hold the ID of the visible tab
			this.activeTab = null;

			// Strore the element
			this.element = $container;

			// Get the ID of the container
			this.id = $container.attr( 'id' );

			// Save an instance of this class
			this.element.data( 'uf', this );

			// Find the hidden error message div
			this.errorMessage = this.element.find( '.error-msg' );

			// Find the succesufull saved div and hide it after a few seconds
			this.successMessage = this.element.find( '.updated:not(.inline)' );
			this.successMessage.each(function() {
				var msg = this;
				setTimeout( function(){
					$( msg ).fadeOut();
				}, 3000 );
			});

			// Attach fields
			this.initFields();

			// Bind validation for the container
			this.bindValidation();

			// Add tabs functionality
			this.initTabs();

			// Jump to a child class constructor if one is defined
			if( typeof( this.initializeContainer ) == 'function' ) {
				this.initializeContainer();
			}
		},

		// Get the fields' DOM elements
		getFields: function() {
			// Get all fields except separator
			return this.$fields = this.element.find( '.uf-field:not(.uf-field .uf-field,.uf-separator)' );
		},

		// Add a field to the fields[] array. If needed,
		// additional actions might be performed
		addField: function( field ) {
			this.fields[ field.id ] = field;
		},

		// Find container fields and create them
		initFields: function() {
			var container = this, deps, i, $fields;

			// Make sure it's known which are the fields
			$fields = this.getFields();

			// For each field, call Field.initField which will route
			// to the right class for the particular field
			$fields.each(function() {
				// Initialize the new field
				var field = Field.initField( this );

				// Push the new field to the container
				container.addField( field );
			});

			_.each( this.fields, function( field ) {
				// Bind a change event to the field
				field.bind( 'valueChanged', function( value ) {
					container.valueChanged( field, value )
				});

				// Collect the field value initially
				container.values[ field.id ] = field.value;
			});

			// Collect dependencies
			this.dependencies = this.getDependencies();

			// After initialization of all fields, which includes collecting their values, check everything
			this.dependencies = UF.dependencies[ this.id ];

			// Turn off jQuery effects during this step
			$.fx.off = true;

			// Check each field
			for( i in this.dependencies ) {
				if( ! (/__inner$/i).test( i ) ) {
					// Check the field's dependencies
					this.checkDependency( this.fields[ i ], this.dependencies[i] );
				}
			}

			// Send the dependencies to fields
			_.each( this.fields, function( field ) {
				if( UF.isset( container.dependencies ) ) {
					if( UF.isset( field.setInnerDependencies ) && UF.isset( container.dependencies[ field.id + '__inner' ] ) ) {
						field.setInnerDependencies( container.dependencies[ field.id + '__inner' ] );
					}
				}
			});

			// Restore jQuery animations
			$.fx.off = false;
		},

		// Get the dependencies for the particular container
		getDependencies: function() {
			var deps = UF.isset( UF.dependencies[ this.id ]) ? UF.dependencies[ this.id ] : {};
			return this.dependencies = deps;
		},

		// Bind the validation of the container to an event.
		bindValidation: function() {
			// This function should be overwritten in typical containers.
			// This does not apply to the repater though.
		},

		// Check if there are errors in the fields
		validate: function() {
			this.status = {
				valid: true,
				errors: []
			};

			// Remove tabs invalid status
			this.element.find( '.nav-tab-wrapper .invalid' ).removeClass( 'invalid' );

			// Check each fields's status
			_.each( this.fields, this.validateField , this );

			return this.status.valid;
		},

		// Validate a single field
		validateField: function( field ) {
			var error = field.check();

			// If there's an error, add it to the container's status
			if( error ) {
				// Scroll to the beginning of the page to make the user see the message
				if( this.status.valid ) {
					this.errorMessage.fadeIn();

					// Hide success message if there is one
					this.errorMessage.siblings( '#message' ).remove();

					$( 'html,body' ).stop(true).animate({
						scrollTop: 0
					});
				}

				this.status.valid = false;
				this.status.errors.push( error );

				// Mark the fields' tab as invalid
				field.row.closest( '.tab' ).each(function(){
					$( '.nav-tab' ).filter( '[href="#' + this.id + '"]' ).addClass( 'invalid' );
				});
			}
		},

		// Initialize tabs in the container
		initTabs: function() {
			var container = this;

			// If there are no tabs, don't do anything
			if( ! this.element.is( '.tabs' ) && ! this.element.find( '.uf-wrap:eq(0)' ).is( '.tabs' ) ) {
				return;
			}

			// Get links and tabs
			this.tabNav = this.element.find( '.nav-tab-wrapper a' );
			this.tabCnt = this.element.find( '.tab' );

			// Bind link click
			this.tabNav.on( 'click', function(e) {
				e.preventDefault();

				container.showTab( $( this ).attr( 'href' ).replace('#', '') );
			});

			// Show the first tab
			if( hash = location.hash.replace(/^#\//, '') ) {
				if( this.tabCnt.filter( '#' + hash ).size() ) {
					// Show the tab from the hash
					this.showTab( hash, false );
				}
			} else {
				// Show the first tab
				this.showTab( this.tabCnt[0].id, false );
			}
		},

		// Switch to a certain tab
		showTab: function( id, animate ) {
			if( id == this.activeTab ) {
				// Nothing to do here too
				return;
			}

			// Check if there are any animations to be done upon change
			if( !UF.isset( animate ) ) {
				animate = true;
			}

			// Add active/inactive classes
			this.tabNav.removeClass( 'nav-tab-active' ).filter( '[href=#' + id + ']' ).addClass( 'nav-tab-active' );
			this.tabCnt.addClass( 'inactive-tab' ).filter( '#' + id ).removeClass( 'inactive-tab' );

			// Save the tab ID
			this.activeTab = id;

			// Add the ID of the tab to the hash
			location.hash = '/' + id;

			// Scroll the window to the tab
			// This is no longer neccessary
			// if( animate )
			// 	$( 'html,body' ).animate({
			// 		scrollTop: this.tabs.offset().top - UF.adminBarHeight
			// 	});
		},

		// This is triggered when a field changes it's value
		valueChanged: function( field, value ) {
			var deps, i;

			// Save the value internally
			this.values[ field.id ] = value;
			
			// For sub-classes do something based on this value
			if( typeof( this.afterValueChanged ) == 'function' ) {
				this.afterValueChanged( field, value );
			}

			// Check each field
			for( i in this.dependencies ) {
				// Don't bother with fields that don't depend on the changed one or have inner dependencies
				if( ! (/__inner$/i).test( i ) && UF.isset( this.dependencies[ i ].targets[ field.id ] ) ) {
					// Check the field's dependencies
					this.checkDependency( this.fields[ i ], this.dependencies[i] );
				}
			}
		},

		// Check if all field dependencies are matched
		checkDependency: function( field, dep ) {
			var visible = dep.relationship == 'AND', valid, i;
			for( i in dep.targets ) {
				valid = this.checkValue( this.values[ i ], dep.targets[ i ].compare, dep.targets[ i ].value );

				if( dep.relationship == 'AND' && !valid ) {
					visible = false;
				}

				if( dep.relationship == 'OR' && valid ) {
					visible = true;
				}
			}
			
			field.row[ visible ? 'show' : 'hide' ]();
		},

		// Compare certain value agains specific rules
		checkValue: function( checkedValue, rule, goodValue ) {
			var valid, currentValue;

			if( typeof( checkedValue ) != 'object' ) {
				checkedValue = [ checkedValue ]
			}

			for( i in checkedValue ) {
				currentValue = checkedValue[ i ];

				switch( rule ) {
					case '>=':
						if( typeof( goodValue ) == 'number' ) {
							valid = currentValue >= parseFloat( goodValue );
						} else {
							valid = currentValue.length >= parseInt( goodValue );
						}
						break;
					case '<=':
						if( typeof( goodValue ) == 'number' ) {
							valid = currentValue <= parseFloat( goodValue );
						} else {
							valid = currentValue.length <= parseInt( goodValue );
						}
						break;
					case '<':
						if( typeof( goodValue ) == 'number' ) {
							valid = currentValue < parseFloat( goodValue );
						} else {
							valid = currentValue.length < parseInt( goodValue );
						}
						break;
					case '>':
						if( typeof( goodValue ) == 'number' ) {
							valid = currentValue > parseFloat( goodValue );
						} else {
							valid = currentValue.length > parseInt( goodValue );
						}
						break;
					case '!=':
						valid = currentValue != goodValue;
						break;
					case 'NOT_NULL':
						valid = currentValue ? true : false;
						break;
					case 'NULL':
						valid = !currentValue;
						break;
					case 'IN':
						if( currentValue.indexOf( ',' ) != -1 ) {
							var i, parts = currentValue.split( ',' );
							valid = false;
							for( i in parts ) {
								if( goodValue.indexOf( parts[i] ) != -1 )
									valid = true;
							}
						} else {
							valid = goodValue.indexOf( currentValue ) != -1;							
						}
						break;
					case 'NOT_IN':
						if( currentValue.indexOf( ',' ) != -1 ) {
							var i, parts = currentValue.split( ',' );
							valid = false;
							for( i in parts ) {
								if( goodValue.indexOf( parts[i] ) != -1 )
									valid = true;
							}
						} else {
							valid = goodValue.indexOf( currentValue ) == -1;							
						}
						break;
					default:
					case '=':
					case '==':
						valid = currentValue == goodValue;
						break;
				}

				if( !valid ) {
					return false;
				}
			}

			return true;
		}
	});

	/**
	 * Options page class
	 */
	UF.ContainerOptions = function( $container ) {
		this.constructContainer( $container );
	}

	// Inherit the Container class
	_.extend( UF.ContainerOptions.prototype, UF.Container.prototype );

	// Add additional methods and properties to the options page container
	_.extend( UF.ContainerOptions.prototype, {
		// Bind validation on form submit
		bindValidation: function() {
			var container = this;

			this.element.on( 'submit', 'form', function( e ) {
				var valid;

				container.element.find( '.uf-field' ).trigger( 'uf-before-save' );
				valid = container.validate();

				if( ! valid ) {
					return false;
				}

				// Submit the form with AJAX if there are no errors
				container.submit( this );
				return false;
			});
		},

		// Pass data to the server through AJAX
		submit: function( form ) {
			var container = this, $f, $btn, $loader, $header;

			this.form = $f = $( form );
			$btn      = $f.find( 'input[type=submit].button-primary' ),
			$loader   = $f.find( '.ajax-loader' ),
			$header   = $f.siblings( 'h2' );

			// Display the loading icon and disable the buttons
			$loader.addClass('loading');
			$btn.attr('disabled', 'disabled');

			// Do the ajax itself
			$.ajax({
				type: 'post',
				url: $f.attr( 'href' ),
				data: $f.serialize(),

				// Handle succesufull save
				success: function( data ) {
					var $message = $( "#message", data );

					// Enable submitting
					$loader.removeClass( 'loading');
					$btn.attr( 'disabled', false );

					// Hide/remove old messages and display the new one
					$( '.error-msg' ).hide();
					$header.siblings( '#message' ).remove();
					$header.after( $message );
					$message.fadeIn();

					// Scroll to the top to make the message visible
					setTimeout(function() {
						$('html,body').animate({
							scrollTop: 0
						});
					}, 100);
				},

				// Handle server-side errors
				error: function( jqXHR, textStatus, errorThrown ) {
					$loader.removeClass( 'loading' );
					$btn.attr( 'disabled', false );

					alert( UF.Strings.saveError );
				}
			});
		},

		// Add automatic positioning of the tabs bar
		initFixedTabs: function() {
			var container = this;

			// Find elements
			this.tabsWrap  = this.tabs.find( '.tabs-nav-wrap' );
			this.tabsInner = this.tabs.find( '.tabs-nav-inner' );

			// Adjust height of the tabs
			UF.window.on( 'resize', function() {
				container.setTabsWrapHeight();
				container.setTabsPosition();
			});

			this.setTabsWrapHeight();

			// On window scroll, set position
			UF.window.on( 'scroll', function() {
				container.setTabsPosition();
			}).on( 'resize', function() {
				container.setTabsPosition();
			});
		},

		// Set a height to the tabs wrapper
		setTabsWrapHeight: function() {
			this.tabsWrap.css({
				height: this.tabsInner.outerHeight()
			});
		},

		// Position the tabs nav for horizontal tabs
		setTabsPosition: function() {
			var s = UF.window.scrollTop() + UF.adminBarHeight;

			if( s > this.tabsWrap.offset().top ) {
				this.tabsInner.addClass( 'fixed' ).css({
					top: UF.adminBarHeight,
					left: this.tabsWrap.offset().left,
					right: UF.window.width() - this.tabsWrap.offset().left - this.tabsWrap.width(),
					position: 'fixed'
				});
			} else {
				this.tabsInner.removeClass( 'fixed' ).css({
					top: 0,
					left: 0,
					right: 0,
					position: 'absolute'
				});
			}
		}
	});

	/**
	 * Post Meta Container Class
	 */
	UF.ContainerPostMeta = function( id, data ) {
		var $container;

		// Save the data
		this.panelData = data;

		// Get the container
		$container = $( '#' + id );

		// Save the data and ID
		this.constructContainer( $container );
	}

	// Add a static listener
	_.extend( UF.ContainerPostMeta, {
		listen: function() {
			$( document ).ajaxSend( function( event, jqXHR, ajaxOptions ) {
				if( ajaxOptions.type == "POST" && ajaxOptions.data.indexOf('action=meta-box-order') != -1 ) {
					$( '.uf-field' ).trigger( 'uf-sorted' );
				}
			}) ;
		}
	} );

	// Inherit the container class
	_.extend( UF.ContainerPostMeta.prototype, UF.Container.prototype );

	// Additional methods
	_.extend( UF.ContainerPostMeta.prototype, {
		// After-constructor method
		initializeContainer: function() {
			// Get all elements of the page (selects, toggles, etc)
			this.getPageElements();

			// Bind changes to elements
			this.bindElementEvents();

			// Trigger an initial validation
			this.validatePanel();

			// Change the validation message container
			this.errorMessage = $( '#uf-postmeta-error' );
		},

		// Prepares all elements that the container might depend on
		getPageElements: function() {
			var i;

			// General elements
			this.$elements = {
				toggle: $( 'input.hide-postbox-tog[value="' + this.id + '"]' ).parent(),
				level: $( '#parent_id' ),
				template: $( '#page_template' )
			}

			// Hierarchical taxonomy checkboxes
			if( UF.isset( this.panelData.terms ) ) {
				this.$elements.terms = {};

				for( i in this.panelData.terms ) {
					this.$elements.terms[ i ] = $( '#' + i + 'checklist input[type=checkbox]' );
				}
			}
		},

		// Bind events to elements
		bindElementEvents: function() {
			var field = this, i;

			this.$elements.toggle.change(function() { field.validatePanel() });
			this.$elements.level.change(function() { field.validatePanel() });
			this.$elements.template.change(function() { field.validatePanel() });

			for( i in this.$elements.terms ) {
				this.$elements.terms[ i ].change( function() { field.validatePanel() } );
			}
		},

		// Checks conditions for the panel and "validates" it
		validatePanel: function() {
			var valid = this.checkTemplates() && this.checkLevels() && this.checkTerms();

			this.element.stop( true, true )[ valid ? 'slideDown' : 'slideUp' ]();
			this.$elements.toggle[ valid ? 'show' : 'hide' ]();
		},

		// Check templates
		checkTemplates: function() {
			var count = 0, i, template;

			// If there's no dropdown for templates, this has no point
			if( ! this.$elements.template.size() ) {
				return true;
			}

			// Get the current template
			template = this.$elements.template.val();

			// Check hidden templates
			for( i in this.panelData.templates_hidden ) {
				count++;

				// If the chosen template is hidden, straightly quit
				if( template == i ) {
					return false;
				}
			}

			// Check for visible templates
			for( i in this.panelData.templates ) {
				count++;

				// If the template is the right one, it's okay
				if( template == i ) {
					return true;
				}
			}

			return count == 0;
		},

		// Check levels
		checkLevels: function() {
			var level = 1,
				count = 0,
				i;

			// No different levels, nothing to do here
			if( ! this.$elements.level.size() ) {
				return true;
			}

			// Get the current level
			this.$elements.level.children( ':selected' ).each(function() {
				var c = this.className;

				if( typeof(c) != 'undefined' ) {
					level = parseInt( c.replace(/^level\-(\d+)$/i, '$1') ) + 2;
				}

				if( isNaN( level ) ) {
					level = 1;
				}
			});

			// Check visible levels
			for( i in this.panelData.levels ) {
				count++;

				// All good
				if( i == level ) {
					return true;
				}
			}

			// Check hidden levels
			for( i in this.panelData.levels_hidden ) {
				count++;

				// Hide the panel from the level
				if( i == level ) {
					return false;
				}
			}

			return ! count;
		},

		// Checks terms
		checkTerms: function() {
			var valid = true, taxonomy, hasItems, i, items, hidden;

			// Check each taxonomy
			for( taxonomy in this.panelData.terms ) {
				// Shortcuts for acceptable and hidden terms
				items  = this.panelData.terms[ taxonomy ];
				hidden = this.panelData.terms_hidden[ taxonomy ];

				// No categories, no point to choose
				if( !this.$elements.terms[ taxonomy ].size() ) {
					continue;
				}

				// Check if there are items for this taxonomy
				hasItems = false;
				for( i in items ) {
					hasItems = true;
					break;
				}

				if( hasItems ) {
					has_checked = false;

					for( i in items ) {
						if( this.$elements.terms[ taxonomy ].filter( '[value=' + i + ']' ).is( ':checked') ) {
							has_checked = true;
						}
					}

					// Nothing checked
					if(!has_checked) {
						valid = false;
					}
				}

				// If there are terms where this should be hidden
				for( i in hidden ) {
					if( this.$elements.terms[ taxonomy ].filter( '[value=' + i + ']' ).is( ':checked') ) {
						valid = false;
					}
				}
			}			

			return valid;
		},

		// Bind validation on form submit
		bindValidation: function() {
			var container = this;

			this.element.closest( 'form' ).on( 'submit', function( e ) {
				var valid = container.validate();

				if( ! valid ) {
					$( '.spinner' ).hide();
					$( '.button-primary-disabled' ).removeClass( 'button-primary-disabled' ).attr( 'disabled', false );
					return false;
				}
			});
		}
	});

	// Simple text input
	Field.extend( 'Text', {
		// Initial constructor
		initialize: function() {			
			// Hold autocomplete suggestions
			this.suggestions = [];

			// Try intializing autocomplete
			this.initAutocomplete();
		},

		// Binds events for input changes, which happen on key up
		bindChange: function() {
			var field = this;

			this.row.on( 'keyup', 'input', function() {
				var $el = $(this);
				field.setValueFromInput( $el.val(), $el );
			} );
		},

		// Trigger change to the input
		triggerChange: function() {
			this.row.find( 'input' ).trigger( 'keyup' );
		},

		// Collect autocomplete suggestions
		prepareSuggestions: function() {
			var $source = this.row.find('.uf-autocompletes');

			if( $source.size() ) {
				this.suggestions = $.parseJSON( $source.html() );
			}
		},

		// Initialize autocomplete, might be overwritteh to replace jQuery UI
		autocomplete: function() {
			this.input.autocomplete({
				source: this.suggestions
			});
		},

		// Initialize the autocomplete functionality
		initAutocomplete: function() {	
			this.prepareSuggestions();
			// If there are suggestions, initialize jQUery UI Autocomplete
			if( this.suggestions.length ) {
				this.autocomplete();	
			}
		}
	});

	// Textarea, Header and Footer scripts fields
	Field.extend( 'Textarea,HeaderScripts,FooterScripts', {
		// Binds events for input changes, which happen on key up
		bindChange: function() {
			var field = this;

			this.row.on( 'keyup', 'textarea', function() {
				var $el = $(this);
				field.setValueFromInput( $el.val(), $el );
			} );
		},

		// Trigger change to the input
		triggerChange: function() {
			this.row.find( 'textarea' ).trigger( 'keyup' );
		}
	});


	// Select field
	Field.extend( 'Select,SelectPage,SelectTerm', {
		initialize: function() {
			var field = this;

			if( UF.isset( this.data.chosen ) ) {
				this.input.filter( 'select' ).each(function() {
					field.custom( $(this) );
				});
			}
		},

		// Initialise a custom select
		custom: function( $input ) {
			$input.select2();
		}
	});

	// Radio group
	Field.extend( 'Radio', {
		// Binds events for input changes, which happen on key up
		bindChange: function() {
			var field = this;

			this.row.on( 'change', 'input', function() {
				var $el = $(this);

				// Only save the value of the checked input
				if( $el.is( ':checked' ) ) {
					field.setValueFromInput( $el.val(), $el );
				}
			} );
		},

		// Trigger change to the input
		triggerChange: function() {
			this.row.find( 'input:checked' ).trigger( 'change' );
		}
	});

	// Richtext field
	Field.extend( 'Richtext', {
		initialize: function() {
			var field = this;

			// This will hold all editors
			this.editors = [];

			// Collect all editors
			this.input.each(function(){
				var $t = $(this);

				// Collect each editor's data
				field.editors.push({
					// The HTML container of the editor
					$container: $t,

					// The code that's the backbone when initializing the editor
					originalCode: $t.parent().html(),

					// ID of the new editor
					id: ('UFFieldRichtext' + (Field.Richtext.i++)).toLowerCase(),

					// The ID placeholder that will need to get replaced with the new ID
					mceId: $t.data('mce-id')
				});
			});

			// Initialize all editors
			this.initEditors();
		},

		// Initialize all editors
		initEditors: function() {
			for( i in this.editors ) {
				// Add the porper ids to the code and trigger tinyMCE
				this.initEditor( this.editors[i] );
			}
		},

		// Initialize a single editor
		initEditor: function( editor ) {
			var field = this, $parent;

			// Save the parent
			$parent = editor.$container.parent();

			// Get the code for the editor and add the proper ID
			$parent.html( editor.originalCode.replace(new RegExp( editor.mceId, 'gi' ), editor.id ) );

			// Restore the container
			editor.$container = $parent.children();

			// Initialize the editor
			this.initMce( editor.id );

			// Bind regeneration
			this.row.on( 'uf-sorted', function() {
				field.regenerate( editor );
			});

			// Bind saving - what's in the editor is not always in the textarea
			this.row.on( 'uf-before-save', function() {
				field.value = field.getValue( editor );
				wpActiveEditor = null;
			});
		},

		// Upon DOM movement, regenerates the editor
		regenerate: function( editor ) {
			var $parent, value;

			// Get the current value
			value = this.getValue( editor );

			// Get the parent
			$parent = $( editor.$container.parent() );

			// Don't make the same editor
			editor.id += 1;

			// Restore the backbone
			$parent.html( editor.originalCode.replace(new RegExp( editor.mceId, 'gi' ), editor.id ) );

			// Restore jQuery objects
			editor.$container = $parent.children();

			// Restore the value
			editor.$container.find('textarea').val( value );

			// Setup the editor
			this.initMce( editor.id );
			wpActiveEditor = null
		},

		// Initialize an editor
		initMce: function( id ) {
			var oldId, i;

			// Get an existing ID
			// This uses the last available editor's config, but we preffer uf_dummy_editor_id 
			// for( i in tinyMCEPreInit.mceInit ) oldId = i; }

			// Setup the Richtext editor
			var mceInit = $.extend({}, tinyMCEPreInit.mceInit[ 'uf_dummy_editor_id' ], { body_class: id, elements: id, rows: $('#' + id).attr('rows'), selector: '#' + id });
			tinyMCEPreInit.mceInit[id] = $.extend({}, mceInit);
			tinymce.init( tinyMCEPreInit.mceInit[id] );

			// Setup quicktags
			var qtInit = $.extend({}, tinyMCEPreInit.qtInit[ 'uf_dummy_editor_id' ], { id: id });
			tinyMCEPreInit.qtInit[id] = $.extend({}, qtInit);
			quicktags(tinyMCEPreInit.qtInit[id]);

			// Init QuickTags
			QTags._buttonsInit();
		},

		// Get the value of an editor
		getValue: function( editor ) {
			var value;

			if( UF.isset( tinyMCE.get( editor.id ) ) ) {
				value = tinyMCE.get( editor.id ).getContent()
				editor.$container.find( 'textarea' ).val( tinyMCE.get( editor.id ).getContent() );
				return value;
			} else {
				return '';
			}
		}
	}, {
		// Since each editor needs a different ID, use this and increment it
		i: 0
	});

	// Set field
	Field.extend( 'Set', {
		// Custom after-constructor
		initialize: function() {
			var field = this;

			// Check and initialize sortable
			if( UF.isset( this.data.sortable ) && this.data.sortable ) {
				this.input.each(function() {
					field.initSortable( $( this ) );
				});
			}
		},

		// Initialize sortable set
		initSortable: function( $fieldset ) {
			var field = this,
				separator = this.data.separator,
				$order = $fieldset.find( 'input[type=hidden]' );

			$fieldset.sortable({
				update: function( event, ui ) {
					var sort = [];

					// Don't cache this selection - it has to be done with the 
					// current DOM order
					$fieldset.find( "input[type='checkbox']" ).each(function() {
						sort.push( $( this ).val() );
					});

					// Implode the sort string
					sort = sort.join( separator );

					// Set the order to the right field
					$order.val( sort );
				}
			});

			$fieldset.disableSelection();
		},

		// Bind custom change events
		bindChange: function() {
			var field = this;

			// Bind events separately for each set
			this.input.each(function() {
				var $set = $(this), value;

				$set.on( 'change', 'input:checkbox', function() {
					value = [];

					$set.find( 'input:checked' ).each(function() {
						value.push( this.value );
					});

					field.setValueFromInput( value.join( ',' ), $set );
				});
			});
		},

		// Trigger change to the input
		triggerChange: function() {
			this.row.find( 'input:checkbox' ).trigger( 'change' );
		}
	});

	// Checkbox field
	Field.extend( 'Checkbox', {
		// Bind custom change events
		bindChange: function() {
			var field = this;

			// Bind events separately for each set
			this.row.on( 'change', 'input:checkbox', function() {
				field.setValueFromInput( this.checked, $( this ) );
			});
		},

		// Trigger change to the input
		triggerChange: function() {
			this.row.find( 'input:checkbox' ).trigger( 'change' );
		}
	});

	// File field
	Field.extend( 'File', {
		// Custom constructor
		initialize: function() {
			var field = this;

			// Render each input separately
			this.input.each(function(){
				field.initializeInput( $( this ) );
			});
		},

		// Initializes a single input in case of multiple languages
		initializeInput: function( $wrap ) {
			var field   = this,
				$elements = {
					input: $wrap.find( 'input[type=hidden]' ),
					preview: $wrap.find( '.uf-file-preview' ),
					button: $wrap.find( '.button-primary' ),
					remove: $wrap.find( '.uf-remove-file' )
				};

			// Handle removing
			$elements.remove.click( function( e ) {
				e.preventDefault();
				field.clear( $elements );
			} );

			// Handle choose button clicks. Edit does the same
			$wrap.on( 'click', '.edit-link, .button-primary', function( e ) {
				e.preventDefault();
				field.choose( $elements );
			} );
		},

		// Opens a media popup on click
		choose: function( $elements ) {
			var field = this,
				options = {
					type: this.getType()
				}

			// Add the selected item if there is one
			if( id = $elements.input.val() )
				options.selected = id;

			UF.Media.requestFile( options, function( attachment ) {
				field.selected( $elements, attachment );
			} );
		},

		// Get the type of the needed file. Meant for inheritors
		getType: function() {
			return 'all';
		},

		// Handles file selects
		selected: function( $elements, attachment ) {
			// Set the hidden input's val
			$elements.input.val( attachment.get( 'id' ) );

			// Change items in the preview
			this.changePreview( $elements, attachment );

			// Show the remove button in case it's been hidden
			$elements.remove.show();
		},

		// Changes the preview area
		changePreview: function( $elements, attachment ) {
			// Show the preview
			$elements.preview.fadeIn();

			// Change texts and attributes
			$elements.preview.find( '.file-title' ).text( attachment.get( 'title' ) );
			$elements.preview.find( '.file-link' ).attr( 'href', attachment.get( 'url' ) );
			$elements.preview.find( '.edit-link' ).attr( 'href', attachment.get( 'editLink' ) );
			$elements.preview.find( 'img' ).attr( 'src', attachment.get( 'icon' ) );
		},

		// De-selects the selected file
		clear: function( $elements ) {
			// Remove the value
			$elements.input.val( '' );

			// Hide the preview
			$elements.preview.fadeOut();

			// Hide the remove button too
			$elements.remove.fadeOut();
		},

		// Disable values from going to the parent
		bindChange: function() {},
	});

	/*
		Repeater Row Class, Extends Container
	*/
	UF.RepeaterRow = function( $row, initial ) {
		this.initial = initial;

		this.constructContainer( $row );
	}

	// Inherit the Container class
	_.extend( UF.RepeaterRow.prototype, UF.Container.prototype );

	// Add additional methods and properties to the repater row
	_.extend( UF.RepeaterRow.prototype, {
		// Custom after-constructor
		initializeContainer: function() {
			var container = this;

			// Collect jQuery elements for most items
			this.getElements();

			// Add actions
			this.addActions();

			// Collect row data
			this.data = this.element.data();

			// Add this to the element's data
			this.element.data( 'uf', this );

			// Mark the element as processed
			this.element.addClass( 'ready' );

			// Check titles, etc.
			this.$fields.each(function() {
				var field = $( this ).data( 'uf' );
				container.afterValueChanged( field, field.value );
			});

			// Add the last class to the last field
			this.$fields.last().addClass( 'last' );
			
			// When everything's done, show the row (toggle it) or hide it;
			if( this.initial ) {
				this.element.addClass( 'closed' );
			} else {
				this.element.removeClass( 'closed' );
			}
		},

		// Get elements of the repeater, delete, toggle, etc.
		getElements: function() {
			this.$elements = {
				fieldsWrap: this.element.find( '.uf-inside:eq(0)' ),
				deleteRow: this.element.children( '.delete-row' ),
				toggle: this.element.children( 'h3,.handlediv' ),
				title: this.element.children( 'h3' ).find( '.group-title' )
			}
		},

		// Bind actions to elements
		addActions: function() {
			var row = this;

			// Handle deleting
			this.$elements.deleteRow.on( 'click', function( e ) {
				row.deleteRow();
				e.preventDefault();
			});

			// Handle toggling
			setTimeout( function() {
				row.$elements.toggle.unbind('click click.postboxes' ).on( 'click', function( e ) {
					var $target = $( e.target );

					// On sort, don't toggle
					if( $target.closest('.uf-row').is( '.ui-sortable-helper' ) ) {
						return;
					}

					// Toggle the row and prevent the default click
					row.toggle();

					e.preventDefault();

					return false;
				});
			}, 100 );
		},

		// Delete the row
		deleteRow: function() {
			// Always make the user confirm
			if( ! confirm( UF.Strings.sure ) ) {
				return;
			}
			
			// Remove the actual row
			this.element.remove();

			// If there's an action set, toggle it
			if( UF.isset( this.onDelete ) ) {
				this.onDelete();
			}
		},

		// Toggle the fields part of the row
		toggle: function() {
			// Simply toggle the visibility class
			if( this.element.hasClass('closed') ) {
				this.element.removeClass( 'closed' );
			} else {				
				this.element.addClass( 'closed' );
			}

			UF.window.trigger( 'resize' );
		},

		// Get the fields' DOM elements
		getFields: function() {
			var $table = this.element.find( '.form-table:eq(0)' );

			// Make sure that tbody is not causing troubles
			if( $table.children( '.uf-field' ).size() ) {
				return this.$fields = $table.children( '.uf-field:not(.uf-separator)' );
			} else {
				return this.$fields = $table.children( 'tbody' ).children( '.uf-field:not(.uf-separator)' );				
			}
		},

		// This is triggered when a field changes it's value and it's saved
		afterValueChanged: function( field, value ) {
			if( field.id == this.data.titleField ) {
				this.setTitle( value );
			}
		},

		// Change the value in the title
		setTitle: function( value ) {
			var i, text;

			if( typeof( value ) == 'object' ) {
				for( i in value ) {
					text = value[ i ];
					break;
				}
			} else {
				text = value;				
			}

			this.$elements.title.text( text ).parent()[ text ? 'show' : 'hide' ]();
		},

		// Spreads the sorted event through the fields
		sorted: function() {
			this.$fields.trigger( 'uf-sorted' );
		},

		// Add inner dependencies
		addDependencies: function( dependencies ) {
			var field = this, i;

			this.dependencies = dependencies;

			for( i in this.fields ) {
				this.fields[ i ].triggerChange();

				// Go deepeer
				if( UF.isset( this.fields[ i ].setInnerDependencies ) && UF.isset( dependencies[ i + '__inner' ] ) ) {
					this.fields[ i ].setInnerDependencies( dependencies[ i + '__inner' ] );
				}
			}
		},

		// Checks all fields for valid values
		check: function() {
			var errors = false, i;

			for( i in this.fields ) {
				if( this.fields[ i ].check() ) {
					errors = true;
				}
			}

			this.element[ errors ? 'addClass' : 'removeClass' ]( 'invalid-row' );

			return errors;
		}
	});

	// The repeater itself
	Field.extend( 'Repeater', {
		// Custom constructor
		initialize: function() {
			var field = this;

			// Set elements index
			this.next_input_id = 0;

			// Hold all rows
			this.rows = [];

			// Since there might be none, add a placeholder for dependencies
			this.dependencies = {};

			// Hold the jquery elements
			this.$elements = {
				fields: this.input.children( '.fields' ),
				placeholder: this.input.children( '.fields' ).children( '.placeholder' ),
				prototypes: this.input.children( '.prototypes' ),
				addButton: this.input.children( '.controls' ).find( '.add' ),
				helper: this.input.children( 'h4' )
			}

			// Initialize existing rows
			this.input.find( '.fields:eq(0) > .uf-row' ).each(function() {
				field.initRow( $( this ), true );
			});

			// Init sortable
			this.initSortable();

			// On click on a prototype, directly add it
			this.$elements.prototypes.unbind( 'click' ).on( 'click', '.uf-row', function() {
				var $row = $( this ).clone().removeClass( 'closed' );

				if( $row.is( '.ui-draggable-dragging' ) ) {
					// Don't continue if the field is draggable
					return false;
				}

				// CLone the row and add it to the fields
				$row.appendTo( field.$elements.fields );

				// Init the row
				field.prepareRow( $row );
				field.initRow( $row, false );

				// Hide the placeholder
				field.$elements.placeholder.hide();

				// Close the prototype - don't let WordPress mess up the state
				$( this ).addClass( 'closed' );

				return false;
			} );

			// Handle the Add button click
			this.$elements.addButton.on( 'click', function( e ) {
				field.$elements.prototypes.find( '.uf-row:eq(0)' ).trigger( 'click' );
				e.preventDefault();
			});
		},

		// Init jQuery UI sortable
		initSortable: function() {
			var field = this;

			// Init the sortable part
			this.$elements.fields.sortable({
				axis: 'y',
				handle: '> h3',
				revert: true,
				// containment: field.input,
				// tolerance: 'pointer',
				receive: function( e, ui ) {
					var $newRow = field.$elements.fields.children( '.uf-row:not(.ready)' );

					if( ! $newRow.is( '.ready' ) ) {
						// Initiate row fields
						field.prepareRow( $newRow );
						field.initRow( $newRow, false );

						// Hide the placeholder
						field.$elements.placeholder.hide();
					}
				},
				stop: function( e, ui ) {
					// Send the event to the sorted item
					$( ui.item ).data( 'uf' ).sorted();
				}
			});

			// Init the draggable part
			this.$elements.prototypes.children().children( '.uf-row' ).draggable({
				connectToSortable: this.$elements.fields,
				helper: 'clone',
				revert: 'invalid',
				containment: field.input
			});
		},

		// Deletes a row
		rowDeleted: function( row ) {
			// If needed, show the "No fields message"
			if( ! this.$elements.fields.children( '.uf-row' ).size() ) {
				this.$elements.placeholder.show();
			}

			// If limits have been reached, now there's space again
			if( this.$elements.prototypes.children().size() > 1) {
				this.$elements.prototypes.show();
				this.$elements.helper.show();				
			} else {
				this.$elements.addButton.show();				
			}
		},

		// Replaces neccessary strings
		prepareRow: function( $row ) {
			$row.html( $row.html().replace(new RegExp(this.data.placeholder, 'g'), this.next_input_id) );
		},

		// Inits a row (group)
		initRow: function( $row, initial ) {
			var field = this,
				row = new UF.RepeaterRow( $row, initial ),
				rowIndex,
				type = $row.data( 'uf-id' );

			// Add the row to the fields's holder
			rowIndex = this.next_input_id;
			this.rows[ rowIndex ] = row;

			// Add dependencies if existing
			if( UF.isset( this.dependencies[ type ] ) ) {
				row.addDependencies( this.dependencies[ type ] );
			}

			// Bind the delete handler
			row.onDelete = function() {
				field.rowDeleted( row );

				// Unset the field from the rows array
				delete field.rows[ rowIndex ];

				// Trigger the save method
				field.setValue( field.rowsCount() );
			}

			// Update the value count
			this.setValue( this.rowsCount() );

			// Increase IDs
			this.next_input_id++;

			// If the limit is reached, hide controls
			if( this.rowsCount() == this.data.limit ) {
				// If limits have been reached, now there's space again
				if( this.$elements.prototypes.children().size() > 1) {
					this.$elements.prototypes.hide();
					this.$elements.helper.hide();				
				} else {
					this.$elements.addButton.hide();				
				}
			}
		},

		// Get the count of rows
		rowsCount: function() {
			var p, i = 0;
			for( p in this.rows ) {
				i++;
			}

			return i;
		},

		// Binds events for input changes. Could be inherited later, along with triggerChange
		bindChange: function() {

			var field = this;

			this.row.on( 'change', 'input,select,textarea', function() {
				var $el = $(this);
				field.setValueFromInput( $el.val(), $el );
			} );
		},

		// Trigger change to the input
		triggerChange: function() {
			// There's nothing to trigger, simply fire the change event
			this.trigger( 'valueChanged', this.rowsCount() );
		},

		// Recieves inner dependencies from the container
		setInnerDependencies: function( dependencies ) {
			var field = this, i, type;

			// Save for later
			this.dependencies = dependencies;

			// Spread the dependencies through existing groups
			for( i in this.rows ) {
				type = this.rows[ i ].data.ufId;

				if( UF.isset( dependencies[ type ] ) ) {
					this.rows[ i ].addDependencies( dependencies[ type ] )
				}
			}
		},

		// Checks if the field's value is valid for required fields
		check: function() {
			var errors = false, i;

			// Force validation of each field group
			for( i in this.rows ) {
				if( this.rows[ i ].check() ) {
					errors = true;
				}
			}

			this.row[ errors ? 'addClass' : 'removeClass' ]( 'invalid-repeater' );

			return errors;
		}
	});

})(jQuery, document, window);