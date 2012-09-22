=== PMPro Affiliates ===
Contributors: strangerstudios
Tags: pmpro, paid memberships pro, ecommerce, affiliates
Requires at least: 3.1
Tested up to: 3.4
Stable tag: .2.2

Create affiliate accounts and codes. If a code is passed to a page as a parameter, a cookie is set. If a cookie is present after checkout, the order is awarded to the affiliate account.

You must have the latest version of Paid Memberships Pro installed (currently 1.4.7).


== Description ==

Story
* Admin creates affiliate account and code.
* If affiliate code is passed as a parameter, a cookie is set for the specified number of days.
* If a cookie is present after checkout, the order is awarded to the affiliate.
* Reports in the admin, showing orders for each affiliate.
* Associate an affiliate with a user to give that user access to view reports.

Questions
* Allow setting of fees?
* Track recurring orders?
* Affiliate reports in front end or back end? How much to show affiliates.

== Installation ==

1. Upload the `pmpro-affiliates` directory to the `/wp-content/plugins/` directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Manage affiliates through the "affiliates" admin page added under Memberships.

== Frequently Asked Questions ==

= I found a bug in the plugin. =

Please post it in the issues section of GitHub and we'll fix it as soon as we can. Thanks for helping. https://github.com/strangerstudios/pmpro-affiliates/issues

= I need help installing, configuring, or customizing the plugin. =

Please visit our premium support site at http://www.paidmembershipspro.com for more documentation and our support forums.

== Changelog ==
= .2.2 =
* Will add a $0 invoice if someone checks out for a free level with an affiliate code set.
* Added a check to the notification code in the settings header so it wouldn't display NULL in the notification space if WP passes that back.

= .2.1 =
* When checking for an affiliate id on a previous order, checking by user_id instead of subscription_transaction_id. This means that affiliates will be given credit when users upgrade... not just recurring invoices from the original subscription.

= .2 =
* Now adds the affiliate id to any order after is is "added" via the pmpro_added_order hook. This means that recurring payment orders will be marked with the affiliate id if you have your IPN handler, Silent Post URL, or Stripe Web Hook setup properly.
* Affiliate codes are now linked to discount codes with the same code. If an affiliate code is passed, it will automatically use the discount code with the same value. If a discount code is used, it will apply the affiliate code with the same value (unless another affiliate code is already being used).

= .1 =
* Initial release.
