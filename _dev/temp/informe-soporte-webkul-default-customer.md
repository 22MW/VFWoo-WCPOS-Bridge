# Bug report: "Save Customer" (POS default customer) overwrites the administrator user (ID 1) when customer creation fails

**Product:** WooCommerce Point of Sale (Webkul), version 7.1.1
**WordPress:** 7.1.1 (`wp-includes/version.php`), PHP 8.4.10, WooCommerce active
**Severity:** high. A failed action silently modifies an existing, unrelated user (the site administrator) instead of showing an error.

## Summary

In **Point of Sale → Settings → Customer**, clicking **Save Customer** to create the POS default customer can fail inside `wc_create_new_customer()`. That function returns a `WP_Error`. The plugin does not check for it and passes the `WP_Error` object as the `ID` of `wp_update_user()`. PHP casts the object to the integer `1`, so the plugin **updates user ID 1** (the administrator) with the name and nickname typed in the form. No error is shown to the operator, only two PHP warnings at the top of the page.

## What we saw

Warnings shown at the top of the Customer settings tab after the failed save:

```
Warning: Object of class WP_Error could not be converted to int in wp-includes/user.php on line 2742
Warning: Object of class WP_Error could not be converted to int in wp-includes/user.php on line 2271
```

- `user.php:2742` is in `wp_update_user()`: `$user_id = (int) ( $userdata['ID'] ?? 0 );`
- `user.php:2271` is in `wp_insert_user()`: `$user_id = (int) $userdata['ID'];`

Database state afterwards (read-only queries):

- No user has the meta `deault_customer_pos`, so **the default customer was not created**.
- User ID 1 (the site administrator) now has `nickname`, `first_name` and `last_name` set to the values that had been typed into the form for the new customer. Their `billing_first_name` was not changed. The original nickname, first name and last name were overwritten and are not recoverable from the site itself (a database backup is needed).

## Root cause (in the plugin code)

File: `includes/filters/class-wc-pos-filter-callbacks.php`, method `wk_wc_save_pos_default_customer()`, around lines 262-272:

```php
if ( false === email_exists( $email ) ) {
    $elm     = explode( '@', $email );
    $elm     = $elm[0];
    $user_id = wc_create_new_customer( $email, $elm, $pwd );   // may return WP_Error
    // Set the nickname.
    wp_update_user(
        array(
            'ID'         => $user_id,                            // WP_Error object -> cast to int 1
            'nickname'   => $email,
            'first_name' => $fname,
            'last_name'  => $lname,
        )
    );
    update_user_meta( $user_id, 'billing_first_name', $fname ); // non-numeric ID, ignored
    ...
    update_user_meta( $user_id, 'deault_customer_pos', true );
```

`wc_create_new_customer()` returns a `WP_Error` when registration is rejected (for example an existing username, a password or email that fails a `woocommerce_registration_errors` rule, or any plugin hooking that filter). The code never calls `is_wp_error( $user_id )`.

## Steps to reproduce

We could not identify **why** `wc_create_new_customer()` returned an error on our site (the error message is discarded), so we cannot give a guaranteed reproduction. The bug is reproducible whenever that function fails:

1. Go to Point of Sale → Settings → Customer with no default customer defined.
2. Fill the form so that `wc_create_new_customer()` returns a `WP_Error` (e.g. choose an email whose local part already exists as a username, or trigger a registration error from a plugin).
3. Click **Save Customer**.
4. Result: the two warnings above, no default customer created, and user ID 1 modified.
5. Expected: an error notice with the `WP_Error` message, and no user modified.

## Suggested fix

```php
$user_id = wc_create_new_customer( $email, $elm, $pwd );
if ( is_wp_error( $user_id ) ) {
    ?>
    <div class="notice notice-error is-dismissible">
        <p><?php echo esc_html( $user_id->get_error_message() ); ?></p>
    </div>
    <?php
    return;
}
```

The same pattern (using the return value of `wc_create_new_customer()` without checking it) should be reviewed in any other place that creates customers.

## Questions for support

1. Can you confirm this behaviour and include the `is_wp_error()` check in the next release?
2. Is there a supported way to create the default customer without this form (for example a hook or a documented user meta), while the fix is not released?
3. Are there other places where a failed customer creation can update an unrelated user?

## Impact and mitigation on our side

- We are not going to use the form again. We will create a normal WooCommerce customer user and mark it with the row action **Set Pos Default Customer** in the Users list.
- The affected administrator account will be corrected manually and, if needed, restored from a database backup.
