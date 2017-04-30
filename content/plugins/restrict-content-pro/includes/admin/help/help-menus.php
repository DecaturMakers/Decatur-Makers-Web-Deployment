<?php


if ( get_bloginfo('version') < 3.3 ) {
	// use old help tab format for WP version less than 3.3
	include('help-menus-setup-old.php');
} else {
	// use the new, better format
	include('help-menus-setup.php');
}

function rcp_render_members_tab_content( $id ) {
	switch( $id ) :

		case 'general' :
			ob_start(); ?>
			<p>This page displays an overview of the registered members on your site, as well as a list of all users, sorted by subscription status.</p>
			<p>By default, only "active" users are shown in the list, but you can choose to show other statuses by simply clicking on the status name, just above the users table.</p>
			<p>On this page, you can perform a variety of tasks, including:</p>
			<ul>
				<li>View subscription details of any registered user</li>
				<li>Edit the subscription details of any registered user</li>
				<li>Activate / Deactivate the subscription of any registered user</li>
				<li>Add new subscriptions to pre-existing users</li>
			</ul>
			<?php
			break;
		case 'adding_subs' :
			ob_start(); ?>
			<p>Adding a premium subscription to an existing user (meaning the user exists in the WordPress database) is easy. At the bottom of the screen, simply choose the user you wish to add a subscription for, choose the subscription level to grant them, choose an expiration date, and click <em>Add User Subscription</em>.</p>
			<p><strong>Note</strong>: when you add a subscription to a user manually, you <em>cannot</em> charge that user for the subscription. This simply allows you to grant access to premium content for special cases, such as when you have given a membership away as a competition prize, or a user has paid with some alternate method.</p>

			<p>Also note, you can also add / modify a user's subscription from the regular WordPress Users page. At the right side of each user entry will be links to Add / Edit Subscriptions.</p>
			<?php
			break;
		case 'member_details' :
			ob_start(); ?>
			<p>The Details page for a member, shows information about that user's subscription, including:</p>
			<ul>
				<li>The Status of the subscription, either Active, Pending, Expired, or Cancelled</li>
				<li>The subscription level the user is signed up with</li>
				<li>The expiration date for the user's subscription</li>
				<li>The Subscription Key, which is the unique identifier key generated when the user signs up</li>
				<li>A list of all (if any) discount codes the member has ever used, both when signing up for the first time and adding another subscription</li>
				<li>A list of all payments that have been made to you from this member</li>
			</ul>
			<?php
			break;
		case 'editing_member' :
			ob_start(); ?>
			<p>The Edit Member page allows administrators to modify details of a member's subscription. The details that can be changed are:</p>
			<ul>
				<li>Status - sets the state of the member's subscription. Only <em>Active</em> members can view premium content</li>
				<li>Subscription Level - sets the subscription package the member is assigned to. This does not affect the member's access level</li>
				<li>Expiration Date - this is the date the member's subscription will expire. When a member's expiration date is reach, their subscription status will be automatically changed to Expired</li>
			</ul>
			<?php
			break;

		default;
			break;

	endswitch;

	return ob_get_clean();
}

function rcp_render_subscriptions_tab_content( $id ) {
	switch( $id ) :

		case 'general' :
			ob_start(); ?>
			<p>Subscription levels allow you to setup different membership packages. For example, you could have one package that grants members access to your premium content for one month, and another that grants users access for an entire year. There is no limit to the number of packages you can create. You can also create "Trial" packages; these grant users premium access for a limited period of time, and can be completely free.</p>
			<p>This page will show you an overview of all the subscription packages you have created on your site. It will also show a variety of details for each package, including the total number of Active subscribers for each level.</p>
			<?php
			break;
		case 'adding_subscriptions' :
			ob_start(); ?>
			<p>Adding new subscription levels is very simple. First, enter the name you want to give the membership package. This name is displayed on the registration form. Second, give your membership package a description. This is also shown on the registration form.</p>
			<p>Next you need to choose the duration for your subscription package. There are several of options for this:</p>
			<ol>
				<li>If you are creating a free, unlimited registration, enter "0" here. This will cause users who register with this package to have no expiration date.</li>
				<li>If you are creating a trial subscription, which will grant users access to premium content for a limited amount of time for free, then choose the length of time you wish the trial to last.</li>
				<li>If you are creating a regular, paid subscription, then simply enter the duration for the subscription.</li>
			</ol>
			<p>Once you have entered a number for the duration, ensure you also choose the correct time unit for the package. This is either <em>Day(s)</em>, <em>Month(s)</em>, or <em>Year(s)</em>.</p>
			<p>Next, enter the price for this subscription. The price will be the amount paid for the duration chosen above. So, for example, if you entered 3 Months above, then this would be the price for 3 months of access to the premium content.</p>
			<p>If you want a free or trial membership, simply enter "0", or choose "Free" from the drop down.</p>
			<?php
			break;
		case 'editing_subscriptions' :
			ob_start(); ?>
			<p>After you have created a subscription, you may edit it at anytime. Making changes to a subscription will have no effect on current subscribers to that subscription, even if you change the price of the package.</p>
			<p>To edit a subscription, click "Edit" on the right side of the screen for the subscription you wish to modify. You will be presented with an edit form to change any and all details of the package. Simply make the changes you need and click "Update Subscription".</p>
			<?php
			break;
		case 'deleting_subscriptions' :
			ob_start(); ?>
			<p>If at anytime you wish to remove a subscription, you may do so by clicked "Delete" on the right side of the screen, from the Subscription Levels page. A popup notification will appear, alerting you that you are about to remove the level permanently. If you confirm, the data for the subscription level will be deleted, with no way to get it back.</p>
			<p><strong>Note</strong>: when you delete a subscription, all subscribers of that subscription will have their status changed to <strong>Cancelled</strong>, meaning that all of them will have their access to premium content revoked.</p>
			<p>If you are going to delete a subscription with active subscribers, it is advised that you first change the subscription level of each of the subscribers before deleting the membership package.</p>
			<?php
			break;

		default;
			break;

	endswitch;

	return ob_get_clean();
}

function rcp_render_discounts_tab_content( $id ) {
	switch( $id ) :

		case 'general' :
			ob_start(); ?>
			<p>Discount codes all you to give special offers to new registrations, giving extra incentive for users to sign up for your website's premium content section. Restrict Content Pro's discount codes work just like any other. There are two kinds:</p>
			<ul>
				<li>Flat - a flat dollar amount discount. This will take the specified number of dollars (or whatever your currency is) off of the base subscription price.</li>
				<li>Percentage - a discount based on a percentage amount. So if your subscription is $10, and your discount is 10%, the registration price will be $9.</li>
			</ul>
			<?php
			break;
		case 'adding_discounts' :
			ob_start(); ?>
			<p>You may create an unlimited number of discount codes, and adding them is simple. From the Discount Codes menu page, simply fill out the form for Add New Discount.</p>
			<ul>
				<li>Name - This is just used for your own administrative / organizational purposes.</li>
				<li>Description - This is used to describe the discount code, and only used for administrative / organizational purposes.</li>
				<li>Code - This is the actual code that users will enter in the registration form when signing up. The code can be anything you want, though a string of all uppercase letters, that preferably spell out a word or phrase, is recommended. It is best to avoid using spaces.</li>
				<li>Type - This is the type of discount you want this code to give, either flat or percentage. Read "General" for an explanation of code types.</li>
				<li>Amount - This is the amount of discount to give with this code. The discount amount is subtracted from the subscription base price.</li>
			</ul>
			<?php
			break;
		case 'editing_discounts' :
			ob_start(); ?>
			<p>Discount codes can be edited at anytime to change the name, description, code, type, and/or amount. You can also deactivate codes to make them unavailable, but keep them available for future use.</p>
			<p>To edit a discount, click "Edit" on the right side of the screen, next to the discount code you wish to modify. This will bring up a form with all of the discount code's information. Simply change what you wish and click "Update Discount" when finished. You may cancel your editing by clicking "Cancel" at the top of the page.</p>
			<?php
			break;
		case 'using_discounts' :
			ob_start(); ?>
			<p>Discount codes are used when a user registers a new subscription on your site. As long as you have at least one discount code created, there will be an option for the user to enter a code when filling out the registration form.</p>
			<p>If a user enters a discount code, then that code is checked for validity when the form is submitted. If the code is invalid, an error will be shown, and if the code is valid, then the discount will be applied to the subscription price when the user is redirected to the payment gateway.</p>
			<p><strong>Note</strong>: users may only use a discount code one time. When a code is used, it is recorded in the database for that user and may never be used by them again.</p>
			<p>Each time a discount code is used, a count will be increased in the database so that you can see the total number of times a code has been used.</p>
			<p>If you wish to see all the discount codes a particular user has used, click "Details" on the user from the Members page.</p>
			<?php
			break;

		default;
			break;

	endswitch;

	return ob_get_clean();
}

function rcp_render_payments_tab_content( $id ) {
	switch( $id ) :

		case 'general' :
			ob_start(); ?>
			<p>This page is a log of all payments that have ever been recorded with Restrict Content Pro. Each time a payment is made, whether it is a one-time sign up payment, or a recurring subscription payment, it is logged here.</p>
			<p>You can see the subscription package the payment was made for, the date is was made, the total amount paid, and the user that made the payment.</p>
			<p>At the bottom of the payments list, you can also see the total amount that has been earned from subscription payments.</p>
			<p>Payment data is permanent and cannot be manipulated or changed.</p>
			<p><strong>Note</strong>: this page only lists completed payments. It will not display any payments that are pending, voided, or cancelled.</p>
			<?php
			break;
		default;
			break;

	endswitch;

	return ob_get_clean();
}

function rcp_render_settings_tab_content( $id ) {

	switch( $id ) :

		case 'general' :
			ob_start(); ?>
			<p>This Settings page lets you configure all of the options available for Restrict Content Pro. You should set each of the options the desired setting and save these options before attempting to use the plugin very much.</p>
			<p>If you have any trouble with these settings, or anything else with the plugin, you are welcome to request assistance on the <a href="http://pippinsplugins.com/support/forum/restrict-content-pro">support forums</a>.
			<?php
			break;

		case 'messages' :
			ob_start(); ?>
			<p>These are the messages displayed to a user when they attempt to view content that they do not have access to.</p>
			<p><strong>Free Content Message</strong> - this message will be displayed to non-logged in users when they attempt to access a post or page that is restricted to registered users only. In this case, registered users refers to members that have an account on the site, not necessarily users that have a paid subscription. So this message will only be displayed to non-logged in users.</p>
			<p><strong>Premium Content Message</strong> - this message is displayed to users, logged in or not, when they attempt to access premium-members-only content. This message will be displayed even to logged in users, if they do not have an active subscription on the site.</p>

			<p>You may use HTML tags in these messages</p>
			<?php
			break;
		case 'paypal' :
			ob_start(); ?>
			<p>These settings control PayPal integration. At this time, PayPal is the only payment gateway available, but others are expected to be released later.</p>
			<p><strong>PayPal Address</strong> - This is the email address connected to your PayPal account. This is where payments made to your site will be sent.</p>
			<p><strong>Currency</strong> - Choose the currency for your site's subscription packages. All exchange rate calculations will be done by PayPal when a user signs up.</p>
			<p><strong>Currency Position</strong> - Choose the location of your currency sign, either before or after the amount.</p>
			<p><strong>Sandbox Mode</strong> - This option allows you to test the plugin by utilizing PayPal's developer tools. Only those users familiar with PayPal's IPN tester should use this option. Leave this option as <strong>unchecked</strong> in order for your site to function live. Contact <a href="http://pippinsplugins.com/support/forum/restrict-content-pro">support</a> if you need assistance with the IPN tester.</p>
			<?php
			break;
		case 'signup_forms' :
			ob_start(); ?>
			<p>The subscription signup forms can be displayed with the following short codes:</p>
			<ul>
				<li>[register_form] - shows the registration form</li>
				<li>[login_form] - shows the user login form for registered users</li>
				<li>[password_form] - shows the reset password form for registered users</li>
			</ul>
			<p><strong>jQuery Validation</strong> - Check this to enable live, jQuery validation of registration, login, and password reset forms. If this is left unchecked, all error checking will be performed after the form is submitted, and will require reload. All form submissions are validated by the server even if jQuery Validation is disabled. <strong>Note</strong>: the jQuery Validation method will not indicate whether a Discount Code is valid.</p>
			<p><strong>Redirect Page</strong> - This is the page that users are sent to after they have a successful registration. If the user is signing up for a free account, they will be sent to this page and immediately logged in. If the user is signing up for a premium subscription, they will be sent to this page from PayPal, and will <em>not</em> be automatically logged in.</p>
			<p><strong>Registration Page</strong> - This is the page that contains the [register_form] short code. This option is necessary in order to generate the link (to the registration page) used by short codes such as [subscription_details], which shows the details of a user's current subscription, or a link to the registration page if not logged in.</p>
			<p><strong>reCaptcha</strong> - Check this to enable a reCaptcha validation form on the registration form. This is an anti-spam protection and will require that the user enter letters / numbers in a field that match a provided image. This requires that you have a reCaptcha account, which is <a href="https://www.google.com/recaptcha">free to signup for</a>.</p>
			<?php
			break;
		case 'emails' :
			ob_start(); ?>
			<p>These settings allow you to customize the emails that are sent to users when their subscription statuses change. Emails are sent to users when their accounts are activated (after successful PayPal payment), when accounts are cancelled (via PayPal), when a subscription reaches its expiration date, and when a user signs up for a free trial account. Emails are <strong>not</strong> sent when a user's status or subscription is manually changed by site admins.</p>
			<p>Each message that is sent out to users can be customized to your liking. There are a variety of template tags available for use in the emails, and those are listed below (and to the right of the input fields):</p>
			<ul>
				<li>%blogname% - this will display the name of your site</li>
				<li>%username% - this will display the name of the person receiving the email</li>
				<li>%expiration% - this will display the expiration date of the user's subscription</li>
				<li>%subscription_name% - this will display the name of the subscription level the member signed up for</li>
				<li>%subscription_key% - this will display the unique key generated for this user's subscription</li>
			</ul>
			<p>Each of these template tags will be automatically replaced with their values when the email is sent.</p>
			<p><strong>Do not</strong> include any HTML tags in these emails.</p>
			<?php
			break;
		case 'misc' :
			ob_start(); ?>
			<p><strong>Hide Premium Posts</strong> - this option will cause all premium posts to be completely hidden from users who do not have access to them. This is useful if you wish to have content that is 100% invisible to non-authorized users. What this means is that premium posts won't be listed on blog pages, archives, recent post widgets, search results, RSS feeds, or anywhere else. If, when this setting is enabled, a user tries to access a premium post from a direct URL, they will be automatically redirected to the page you choose below.</p>
			<p><strong>Redirect Page</strong> - this is the page non-authorized users are sent to when they try to access a premium post by direct URL.</p>
			<?php
			break;
		case 'logging' :
			ob_start(); ?>
			<p><strong>Enable IPN Reports</strong> - by checking this option, you will enable an automatic email that is sent to the WordPress admin email anytime a PayPal IPN attempt is made. IPN attempts are made when a user signs up for a paid subscription, and when recurring payments are made or cancelled.</p>
			<p>When an IPN attempt is made, it is either Valid, or Invalid. A valid IPN is one that resulted in a successful payment and notification of the payment. An invalid IPN attempt happens when, for whatever reason, PayPal is unable to correctly notify your site of a payment or subscription change.</p>
			<p>With this option enabled, the email address set in the General WordPress Settings will get an email every time an IPD request is made. This is useful for debugging, in the case something is not working correctly.</p>
			<p><strong>Log IPN Errors</strong> - this option does essentially exactly the same thing as above, except logs the IPN attempt in a text file stored on the server. This option also only logs errors, not successful IPN attempts.</p>
			<?php
			break;
		default;
			break;

	endswitch;

	return ob_get_clean();
}