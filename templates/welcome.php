<div class="wrap about-wrap uf-welcome">
	<div class="wp-ui-highlight version">
		<span class="ultimate-fields-icon"></span>
		<strong>Version 1.0.1</strong>
	</div>

	<h1>Welcome to Ultimate Fields 1.0.1</h1>
	<div class="about-text">Thank you for installing Ultimate Fields, one of the most useful plugins for WordPress! I truly apreciate the fact that you are one of the first few people to try the plugin. There are a few things you should know about the plugin before you start using it.</div>
	
	<hr />

	<div class="changelog">
		<h2 class="about-headline-callout">Introducing a cool new plugin</h2>
		<p>Ultimate Fields is a powerful tool that will change your perspective about data entry in WordPress. Still, you will need to learn a few things first. Ultimate Fields, as it&apos;s name says, is quite field-centric. It&apos;s all about fields: their type, their settings, their container, the place where they are displayed. So let&apos;s start with fields. You have a few types of them available to you:</p>
		<table class="wp-list-table widefat">
			<thead>
				<tr>
					<th>Type</th>
					<th>Purpose</th>
				</tr>
			</thead>
			<tr class="alternate">
				<th>Heading</th>
				<td>Displays a heading with optional description below it. It is useful for separating groups of fields by their functionality and/or guide the user for the purpose of the next few fields.</td>
			</tr>
			<tr>
				<th>Text</th>
				<td>Displays a standard text input. If you want to, you could add autocomplete suggestions.</td>
			</tr>
			<tr class="alternate">
				<th>Select</th>
				<td>Allows the user to select a value among multiple ones. The values can be both manually entered or automatically loaded from any post type (pages, posts, etc.).</td>
			</tr>
			<tr>
				<th>Select Page</th>
				<td>Displays a hierarchical drop-down with pages.</td>
			</tr>
			<tr class="alternate">
				<th>Radio</th>
				<td>Similarly to the Select field, the radio field allows the user to select a single option.</td>
			</tr>
			<tr>
				<th>Select Term</th>
				<td>Displays a drop-down with all available terms from a taxonomy.</td>
			</tr>
			<tr class="alternate">
				<th>Set</th>
				<td>Displays a set of checkboxes where the user can select mutiple options. The options are the same as the select field above.</td>
			</tr>
			<tr>
				<th>Textarea</th>
				<td>Displays a basic text area with adjustable number of rows.</td>
			</tr>
			<tr class="alternate">
				<th>Checkbox</th>
				<td>Displays a single checkbox. Useful for toggling functionality.</td>
			</tr>
			<tr>
				<th>File</th>
				<td>Enables uploading and chosing any file through the media uploader.</td>
			</tr>
			<tr class="alternate">
				<th>Rich Text Editor</th>
				<td>Enables input through the WYSIWYG editor.</td>
			</tr>
		</table>

		<h2 class="about-headline-callout">The next step: Containers</h2>
		<p>Above is the list of fields, which are available to you. But they don't mean anything until they are shown somewhere, contained inside something. That is what containers are for. They contain specific fields and display them in specific places.</p>
		<p>The free version of Ultimate Fields has a couple of them:</p>
		
		<img src="<?php echo UF_URL ?>/settings/welcome-assets/post-meta-simple.png" width="500" alt="" class="alignright" />
		<h3>Post Meta Fields: The core</h3>
		<p>Posts, pages and every other post type can have custom information associated with them. Ultimate Fields allows you to add fields to post meta containers, which will be displayed on the edit screen of a particular post type.</p>
		<p>Even more interesting, you can define when those boxes are visible. Want some fields only for a particular template on a particular level? No problem.</p>
		<h4>You can select:</h4>
		<ul>
			<li>Post types that the container works with.</li>
			<li>Levels - you can only show the container when the edited page has a parent.</li>
			<li>Templates - When you work with pages, you can display certain fields only when a specific template is selected.</li>
			<li>Taxonomies - a group of fields would only be visible when a specific category is checked for the current post.</li>
		</ul>
		<div style="clear:both"></div>

		<img class="alignright" src="<?php echo UF_URL ?>/settings/welcome-assets/theme-options-simple.png" width="500" />
		<h3>Options Pages: Perfect for Theme Options</h3>
		<p>Of course, your site would need some global options. WordPress has a lot of them already, but some more would always come in handy, right? Because of that, Ultimate Fields provides Options Pages.</p>
		<p>Options Pages can be displayed wherever you want them to: In the main menu, the appearance menu and etc.</p>
		<p>Both Options Pages and Post Meta support tabs too.</p>
		<div style="clear:both"></div>

		<h2 class="about-headline-callout">Something else?</h2>
		<p>Yup. All of this comes inside a beautiful package - everything is as WordPress as it can be. Nothing more, nothing less - default WordPress style. Everything is as familiar to your users as if it is built-in.</p>
		<p>And what&apos;s in for you? Well, all of this is managed through a sleek interface in the admin. Type titles, drag and drop and you are done. There is no explanation needed. Just go there and see for yourself.</p>
		<img class="aligncenter" src="<?php echo UF_URL ?>/settings/welcome-assets/interface.png" alt="" width="600" />

		<h2 class="about-headline-callout">So far so good... but what makes this plugin unique?</h2>
		<h2>Repeaters.</h2>
		<p>You can create repeatable groups of multiple fields. Not a group, groups. This way you can create content builders, sliders and endlessly more things. As a small example, you can check <a href="<?php echo admin_url( 'post-new.php?post_type=ultimatefields#/fields' ) ?>">the &quot;Fields&quot; tab</a> on the create container page. What you see there is a big repeater.</p>
		<hr />
		<h2>Multilingual Functionality</h2>
		<p>Smart Fields comes with support for multilingual sites. Basically, this means that each field can show a separate input for each enabled language.</p>

		<p>This works with qTranslate which is a multilingual plugin for WordPress. A few word about what it does: WordPress has built-in support for internationalization, but it only works with a single language and needs some settings set through wp-config.php and some files downloaded. With qTranslate, you can simultaneously enable multiple languages and the plugin will download all necessary files. The more important thing the plugin does is add multiple fields for each language - you have (n) fields for title, (n) fields for content, etc.</p>
		<hr />
		<h2>Responsive &amp; Good Looking</h2>
		<p>WordPress 3.7 set a bar for plugins. Ultimate Fields jumps over it.</p>

		<hr />
		<h2>Retina Ready</h2>
		<p>All images and icons are optimized. You will have no issues while using the plugin on mobile devices.</p>

		<h2 class="about-headline-callout">Retrieving values</h2>
		<p>Ultimate Fields saves the data in the database in a perfectly clear format. This means that you can retrieve an option or a custom field with the standard WordPress functions.</p>
		<p>Still, there are a couple of functions, which would come in handy:</p>
		<code>// Show the value of a field<br />uf( 'field_key' );<br /><br />// Show an option<br />uf( 'field_key', 'option' );</code>
		<p>Yes, it's as simple as that. Still if you don&apos;t want to modify your code, you can use the shortcode which works in the exact same way:</p>
		<code>[uf key='field_key']</code>

		<h2 class="about-headline-callout">Developers</h2>
		<p>This plugin allows you to set it&apos;s fields through the admin, but there is an excellent API for developers too. If you use the API directly, you could use field-to-field dependencies, nested repeaters and much more. Check the site of the plugin for detailed documentation.</p>

		<hr />

		<h2 class="about-headline-callout">Like it so far? Check the premium version.</h2>
		<p>If you feel limited with the current functionality, I&apos;d recommend you to try the premium one. You can purchase it through <a href="http://ultimate-fields.com/premium/">Ultimate Fields.com: Premium</a>.</p>
		<h3>Here&apos;s a short list of what Premium gives you:</h3>

		<h4>Containers:</h4>
		<ul>
			<li><strong>Term Meta</strong>: Add fields to any taxonomy you want to including categories, tags, etc.</li>
			<li><strong>User Meta</strong>: Add fields to user profiles.</li>
			<li><strong>Widgets</strong>: You could even create custom widgets, where you can use all fields that are available for any other container.</li>
			<li><strong>Box-less Post Meta</strong>: Display the fields like they were meant to be there, not just some additional information.</li>
		</ul>

		<h4>Fields:</h4>
		<ul>
			<li><strong>Icon</strong> (FontAwesome/DashIcons Font Icon)</li>
			<li><strong>Number</strong> (With a beautiful slider)</li>
			<li><strong>Tags</strong></li>
			<li><strong>Image Select</strong> (Choose Visually)</li>
			<li><strong>Google Font</strong> (Find a font by viewing it before choosing it)</li>
			<li><strong>Map</strong> (Easily pick the location of everything)</li>
			<li><strong>Header Scripts</strong></li>
			<li><strong>Footer Scripts</strong></li>
			<li><strong>Image</strong></li>
			<li><strong>Audio</strong></li>
			<li><strong>Gallery</strong></li>
			<li><strong>Color</strong></li>
			<li><strong>Select Sidebar</strong> (Not just select, you can create sidebars too)</li>
			<li><strong>Date</strong></li>
			<li><strong>Time</strong></li>
		</ul>

		<h4>Shortcode Generator</h4>
		<p>Access all values without a keyboard.</p>

		<h4>Layout Field</h4>
		<p>The layout field extends the power of the repeater to new levels.</p>
		<img class="aligncenter" src="<?php echo UF_URL ?>/settings/welcome-assets/layout-preview.png" alt="" width="600" />

		<h4>Premium Support</h4>
		<p>Guaranteed answers within a few hours.</p>

		<p><a href="http://ultimate-fields.com/premium/" class="button button-hero button-primary button-disabled">Go Premium (Coming Soon)</a></p>

		<hr />

		<p><a href="<?php echo admin_url( 'post-new.php?post_type=ultimatefields' ) ?>" class="button button-hero button-secondary">Create your first container</a></p>
	</div>
</div>

<style type="text/css">
.uf-welcome .version { float: right; padding: 10px 0; text-align: center; background: #0074a2; width: 150px; height: 150px; }
.uf-welcome .version { box-shadow: 0 0 7px rgba(0,0,0,0.3); }
.uf-welcome .version span { font-size: 120px; line-height: 120px; }
.uf-welcome .version strong { display: block; font-weight: 100; font-size: 20px; }
.uf-welcome img { padding: 3px; border: 1px solid #ccc; }
.uf-welcome img.aligncenter { margin-left: auto; margin-right: auto; display: block; }
/*.welcome h1 { border-bottom: 1px solid #ccc; text-align: center; line-height: 100px; font-size: 40px; }
.welcome h1 .ultimate-fields-icon { font-size: 80px; vertical-align: middle; font-weight: normal; }*/
</style>