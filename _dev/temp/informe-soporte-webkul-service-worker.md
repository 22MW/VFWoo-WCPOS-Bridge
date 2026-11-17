# Support request: POS offline mode / PWA does not work because the service worker URL returns 404 (and how do we update the plugin?)

**Product:** WooCommerce Point of Sale (Webkul), version 7.1.1
**Environment:** WordPress 7.1.4, WooCommerce 11.1.1, PHP 8.3, standard Nginx hosting
**License / order number:** [fill in]
**Contact:** [your name and email]

Hello Webkul support team,

We are setting up the POS for a shop that needs to keep selling when the internet drops, and we found that the service worker is never registered. We would also like to know how to update the plugin. Details below.

## 1. The problem

The POS registers its service worker from this address:

```
https://<our-site>/assets/service-worker.js
```

That address returns **404 Not Found** on our production site. The file that the plugin ships is only available inside the plugin folder:

```
https://<our-site>/wp-content/plugins/woo-point-of-sale/assets/service-worker.js   -> 200
https://<our-site>/assets/service-worker.js                                         -> 404
```

In Chrome DevTools → Application → Service workers, with the POS page open, **no service worker is registered** for the site.

### What we found in the code

- `includes/front/class-wc-pos-script-loader.php` (around line 593) sets `'service_worker' => WKWCPOS_SITE_URL . '/assets/service-worker.js'`, and `woo-point-of-sale.php` defines `WKWCPOS_SITE_URL` as `site_url()`. So the registered address is `<site>/assets/service-worker.js`, at the root of the site.
- `api/includes/config/class-wkwcpos-api-config.php` (around line 223) builds the same address without the slash: `WKWCPOS_SITE_URL . 'assets/service-worker.js'`, which gives `https://<our-site>assets/service-worker.js`.
- `includes/class-wkwcpos-service-worker-activator.php` only writes **`wkwcpos-manifest.json`** to the site root (that file does exist and works). It declares `global $tmp_sw;` but never writes a service worker file. Nothing copies `assets/service-worker.js` to the root.
- The compiled app (`assets/dist/app/index.js`) calls `navigator.serviceWorker.register(apif_script.service_worker)` and only logs a message on failure, so the operator sees no error.
- `assets/service-worker.js` precaches relative URLs such as `dist/app/index.js`, which only match the plugin's own `assets/` folder, not `<site>/assets/`.

### Impact

- The app files are not cached, so the POS **cannot start without a connection**. It only keeps working offline if the page was already open when the connection dropped.
- The installable-app (PWA) mode adds a manifest but no offline behaviour.

## 2. Questions

1. Is this a known issue in 7.1.1? Is `<site>/assets/service-worker.js` really the intended address, or should the service worker be registered from the plugin folder?
2. How is the service worker supposed to control the POS page (our POS endpoint is a custom path)? A worker served from `/assets/` or from the plugin folder has a scope that does not include the POS page unless it is served with the `Service-Worker-Allowed` header or from the site root. What is the supported way to serve it?
3. Is there a fixed version, or a patch we can apply?
4. Is there a recommended way to make the POS start offline (cache the POS page and files) with the current architecture?

## 3. Updating the plugin

We run **7.1.1**. Could you tell us:

- What is the **latest version** and where do we get it (our account downloads, or a dashboard update using our license key)? We noticed the bundled `wk-plugin-updates` module: is that the update channel, and what does it need to be set up?
- Is the update safe to run over an existing installation with data (orders, outlets, drawer reports, POS customers)? Are there **database migrations** or steps to repeat afterwards (save the endpoint settings again, regenerate the manifest, flush rewrite rules, clear the local POS data / run Resync)?
- Is the latest version compatible with **WordPress 7.x, WooCommerce 11.x and PHP 8.3 or newer**?
- Could you send us the **changelog** from 7.1.1 onwards?

We have not modified any file of the plugin. We can send more information, a screen recording or access to a staging copy if that helps.

Thank you very much.

Best regards,
[your name]
