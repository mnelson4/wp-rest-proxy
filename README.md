# WP REST API Proxy

WordPress Plugin that allows the site's REST API requests to act as a proxy for another site.

Eg, if your site is at `mysite.com`, the request `GET http://mysite.com/wp-json/wp/v2/posts?proxy_for=http://othersite.com/` will return the posts from othersite.com.

This is useful if you have Javascript on your site that needs to retrieve WP REST API data from another site, but that site hasn't configured CORS to allow your site's requests.

* Note: Currently only forwards GET requests.
* This software is provided as-is, with no 
