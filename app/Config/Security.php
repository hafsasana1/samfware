<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Security extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * CSRF Protection Method
     * --------------------------------------------------------------------------
     *
     * Protection Method for Cross Site Request Forgery protection.
     *
     * @var string 'cookie' or 'session'
     */
    public string $csrfProtection = 'session';

    /**
     * --------------------------------------------------------------------------
     * CSRF Token Randomization
     * --------------------------------------------------------------------------
     *
     * Randomize the CSRF Token for added security.
     */
    public bool $tokenRandomize = true;

    /**
     * --------------------------------------------------------------------------
     * CSRF Token Name
     * --------------------------------------------------------------------------
     *
     * Token name for Cross Site Request Forgery protection.
     */
    public string $tokenName = 'csrf_test_name';

    /**
     * --------------------------------------------------------------------------
     * CSRF Header Name
     * --------------------------------------------------------------------------
     *
     * Header name for Cross Site Request Forgery protection.
     */
    public string $headerName = 'X-CSRF-TOKEN';

    /**
     * --------------------------------------------------------------------------
     * CSRF Cookie Name
     * --------------------------------------------------------------------------
     *
     * Cookie name for Cross Site Request Forgery protection.
     */
    public string $cookieName = 'csrf_cookie_name';

    /**
     * --------------------------------------------------------------------------
     * CSRF Expires
     * --------------------------------------------------------------------------
     *
     * Expiration time for Cross Site Request Forgery protection cookie.
     *
     * Defaults to two hours (in seconds).
     */
    public int $expires = 7200;

    /**
     * --------------------------------------------------------------------------
     * CSRF Regenerate
     * --------------------------------------------------------------------------
     *
     * Regenerate CSRF Token on every submission.
     */
    public bool $regenerate = true;

    /**
     * --------------------------------------------------------------------------
     * CSRF Redirect
     * --------------------------------------------------------------------------
     *
     * Redirect to previous page with error on failure.
     *
     * @see https://codeigniter4.github.io/userguide/libraries/security.html#redirection-on-failure
     */
    public bool $redirect = (ENVIRONMENT === 'production');

    /**
     * --------------------------------------------------------------------------
     * SECURITY HEADERS
     * --------------------------------------------------------------------------
     *
     * Configure additional security headers for comprehensive protection
     */
    
    /**
     * X-Frame-Options: Prevent clickjacking attacks
     * 'DENY' - Prevents framing in any context
     * 'SAMEORIGIN' - Allows framing only by same origin
     */
    public string $frameOptions = 'DENY';
    
    /**
     * X-Content-Type-Options: Prevent MIME type sniffing
     * Must be set to 'nosniff'
     */
    public string $contentTypeOptions = 'nosniff';
    
    /**
     * X-XSS-Protection: Enable browser XSS filter (legacy, but still useful)
     * Format: '1; mode=block'
     */
    public string $xssProtection = '1; mode=block';
    
    /**
     * Strict-Transport-Security: Force HTTPS connections
     * Format: 'max-age=31536000; includeSubDomains; preload'
     * max-age: 1 year in seconds
     * includeSubDomains: Apply to all subdomains
     * preload: Allow inclusion in HSTS preload list
     */
    public string $hsts = 'max-age=31536000; includeSubDomains; preload';
    
    /**
     * Content-Security-Policy: Prevent various injection attacks
     */
    public string $csp = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://fonts.googleapis.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: https:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self';";
    
    /**
     * Referrer-Policy: Control referrer information
     * 'no-referrer' - Don't send referrer
     * 'strict-no-referrer' - Never send referrer
     * 'no-referrer-when-downgrade' - Don't send referrer for HTTPS->HTTP
     * 'same-origin' - Send referrer only for same origin
     */
    public string $referrerPolicy = 'strict-no-referrer';
    
    /**
     * Permissions-Policy: Control browser features
     * Restricts access to powerful browser features
     */
    public string $permissionsPolicy = 'geolocation=(), microphone=(), camera=(), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=();';
}

