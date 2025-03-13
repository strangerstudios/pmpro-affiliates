=== Paid Memberships Pro - Affiliates Add On ===
Contributors: strangerstudios
Tags: pmpro, paid memberships pro, ecommerce, affiliates
Requires at least: 5.2
Tested up to: 6.8
Stable tag: 0.6.2

Create affiliate accounts with unique referrer URLs to track membership checkouts.

== Description ==

Create affiliate accounts and codes. If a code is passed to a page as a parameter, a cookie is set. If a cookie is present after checkout, the order is awarded to the affiliate account.

You must have the latest version of Paid Memberships Pro installed (currently 1.4.7).

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
1. Affiliate URLs will look like http://site.com/?pa=AFFILIATECODE
1. You can add a "subid" to the URL on the fly for more granular tracking http://site.com/?pa=AFFILIATECODE&subid=TEST1
1. Create a page with the [pmpro_affiliates_report] shortcode and direct your affiliate users to that page.

== Frequently Asked Questions ==

= I found a bug in the plugin. =

Please post it in the issues section of GitHub and we'll fix it as soon as we can. Thanks for helping. https://github.com/strangerstudios/pmpro-affiliates/issues

= I need help installing, configuring, or customizing the plugin. =

Please visit our premium support site at http://www.paidmembershipspro.com for more documentation and our support forums.

== Changelog ==
= 0.6.2 - 2025-03-13 =
* SECURITY: Improved the escaping of strings throughout the plugin. #58 (@dparker1005)
* ENHANCEMENT: Added a new filter `pmpro_affiliates_commission_calculation_source` to allow calculating commissions based on the order total or subtotal. #53 (@JarrydLong)
* BUG FIX: Fixed an issue where orders that were not associated with affiliates would still show in the affiliates report. #55 (@dparker1005)
* BUG FIX: Fixed an issue where some strings would not be translated. #57 (@andrewlimaza)

= 0.6.1 - 2024-01-05 =
* ENHANCEMENT: Updating `<h3>` tags to `<h2>` tags for better accessibility. #51 (@kimwhite)
* BUG FIX: Fixed a warning on the reports page when the affiliate name was missing from the report. #52 (@andrewlimaza)
* BUG FIX: Fixed an issue with exporting the affiliates CSV wouldn't include the affiliates name. #52 (@andrewlimaza)
* REFACTOR: Now using the `pmpro_default_discount_code` filter for sites using PMPro v3.0+ instead of setting discount code by altering request variables. #50 (@dparker1005)

= 0.6 -2023-01-10 =
* SECURITY: General improvements to sanitization and escaping of strings and SQL queries.
* ENHANCEMENT: Added ability to track commission rate and mark affiliate commission/orders as paid or reset them to unpaid. Defaults to 0% to keep backwards compatibility.
* ENHANCEMENT: Added "PMPro Affiliates Report" block.
* ENHANCEMENT: Improved frontend affiliate report to be responsive for mobile devices.
* ENHANCEMENT: Added the ability to search for affiliates based on the affiliate name or email within the admin area.
* ENHANCEMENT: General improvements to UI/UX of the affiliates admin area. Added various links to navigate between affiliate and user information more easily.
* ENHANCEMENT: Added autocomplete username functionality when creating an affiliate.
* ENHANCEMENT: Improved logic to figure out affiliate orders during checkout (Only loads relevant code on the checkout page).
* ENHANCEMENT: Added filter 'pmpro_affiliates_autocomplete_user_search_limit' to adjust the number of users returned in the autocomplete search when adding an affiliate. Defaults to 25.

= 0.5 - 2022-01-29 =
* BUG FIX: Fixed warning and broken functionality for the "View All" back link for frontend user affiliate reports.
* BUG FIX/ENHANCEMENT: Now localizing dates using the date_i18n() function.
* BUG FIX/ENHANCEMENT: Fixed incorrect textdomain for a few localized strings.
* BUG FIX/ENHANCEMENT: Improved "How to Create Links" language for the frontend affiliate report.
* ENHANCEMENT: Implemented the WP POT/PO/MO Generator action.
* ENHANCEMENT: Added `pmpro_affiliate_report_extra_cols_header` and `pmpro_affiliate_report_extra_cols_body` hooks to show extra data on the Reports table in WP admin.
* ENHANCEMENT: Added `pmpro_affiliate_extra_cols_header` and `pmpro_affiliate_extra_cols_body` hooks to show extra data on the Affiliates table in WP admin.
* ENHANCEMENT: Added `pmpro_affiliate_list_csv_extra_columns` and `pmpro_affiliate_list_csv_extra_column_data` filters to add extra data to the Affiliate report export to CSV.
* ENHANCEMENT: Added `pmpro_affiliate_default_cookie_duration` filter adjust the cookie days default value when manually creating a new affiliate.

= 0.4.1 - 2021-01-19 =
* BUG FIX/ENHANCEMENT: Adjusted queries to only include credit for orders not in specific statuses.
* ENHANCEMENT: Added `pmpro_affiliates_new_code` filter to allow custom code to modifty the generated Affiliate Codes.
* ENHANCEMENT: Now generating the affiliate codes using the pmpro_getDiscountCode function.
* ENHANCEMENT: Localized the plugin for translation.
* ENHANCEMENT: Added 'Membership Level' to the Affiliate Report admin page and export CSV.

= 0.4 - 2020-07-13 =
* BUG FIX: Fixed issue where recurring orders weren't tracked as affiliate sales even if you set an affiliate to get credit for renewals.
* BUG FIX: No longer overriding the default character set when adding the DB tables on install.
* ENHANCEMENT: Moved some links on the affiliates page in the dashboard to "row actions".
* ENHANCEMENT: Added an !!ORDER_AMOUNT!! variable to use in the tracking code.
* REFACTOR: Created functions to get options and settings, avoiding warnings in different versions of PHP.

= .3.1 =
* BUG/ENHANCEMENT: Updating the "Affiliates" submenu page to support PMPro v2.0+ Dashboard menu.
* ENHANCEMENT: Adding filter 'pmproaf_default_cookie_duration' for adjusting default cookie duration.
* ENHANCEMENT: Updated Plugin URI, Author, and internal links to documentation pages.

= .3 =
* BUG FIX: Removed "trying" from the frontend affiliates page. (Thanks, ttshivers on GitHub)
* BUG FIX/ENHANCEMENT: Now also checking the $post->post_content_filtered value when looking for the pmpro_affiliates_report shortcode. This helps with certain themes (e.g. Layers) that may have empty post_content. (Thanks, ttshivers on GitHub)
* ENHANCEMENT: Now set a membership level to generate an affiliate for the user after membership checkout.
* ENHANCEMENT: Set the frontend "Affiliate Report" page under Memberships > Page Settings.
* ENHANCEMENT: Now you can customize the name your "program" (i.e. Affiliates, Referrals, Invitations), from the Memberships > Affiliates admin page.
* ENHANCEMENT: Added row alternate coloring in admin report views.

= .2.5 =
* ENHANCEMENT: Now tracks visits as well as conversions.
* ENHANCEMENT: Added Delete link to Affiliates admin page.

= .2.4.1 =
* BUG FIX: Replaced $wpdb->escape calls with esc_sql to avoid notice.

= .2.4 =
* BUG FIX: Fixed SQL bug that came up on some setups (typically Windows-based) where affiliates wouldn't insert. (Thanks, Jose Fernandez)

= .2.3 =
* ENHANCEMENT: Added affiliates link to admin bar.
* ENHANCEMENT: Affiliate report export to CSV.
* ENHANCEMENT: Frontend report for designated affiliate users.

= .2.2 =
* BUG FIX/ENHANCEMENT: Added a check to the notification code in the settings header so it wouldn't display NULL in the notification space if WP passes that back.
* ENHANCEMENT: Will add a $0 invoice if someone checks out for a free level with an affiliate code set.

= .2.1 =
* BUG FIX/ENHANCEMENT: When checking for an affiliate id on a previous order, checking by user_id instead of subscription_transaction_id. This means that affiliates will be given credit when users upgrade... not just recurring invoices from the original subscription.

= .2 =
* ENHANCEMENT: Now adds the affiliate id to any order after is is "added" via the pmpro_added_order hook. This means that recurring payment orders will be marked with the affiliate id if you have your IPN handler, Silent Post URL, or Stripe Web Hook setup properly.
* ENHANCEMENT: Affiliate codes are now linked to discount codes with the same code. If an affiliate code is passed, it will automatically use the discount code with the same value. If a discount code is used, it will apply the affiliate code with the same value (unless another affiliate code is already being used).

= .1 =
* Initial release.
