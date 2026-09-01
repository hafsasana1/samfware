<?php

namespace App\Controllers;

use App\Models\HomeModel;
use App\Libraries\InputSanitizer;

class Home extends BaseController
{
    protected object|null $app;
    protected HomeModel $homeModel;
    protected $db;

    public function __construct()
    {
        helper(['url', 'cookie', 'global_function', 'query_cache']);
        $this->app       = session()->get('fw');
        $this->homeModel = new HomeModel();
        $this->db        = \Config\Database::connect();
    }

    public function ptest(): string
    {
        echo '<pre>getMappingRoute removed — CI4 incompatible</pre>';
        return '';
    }

    public function fwIndex(): \CodeIgniter\HTTP\RedirectResponse
    {
        if (session()->get('fw') || isLoggedIn()) {
            return redirect()->to(base_url(ADMIN_PATH . '/dashboard'));
        }
        return redirect()->to(base_url(ADMIN_PATH . '/login'));
    }

    public function doLogin(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (isLoggedIn()) {
            return redirect()->to(base_url(ADMIN_PATH . '/dashboard'));
        }

        // CI3 mein isset($_POST['submit']) tha — same logic
        if ($this->request->getMethod() === 'POST' && $this->request->getPost('submit')) {

            $username = trim($this->request->getPost('username'));
            $password = trim($this->request->getPost('password'));
            $sanswer  = trim($this->request->getPost('sanswer'));
            $ipAddress = $this->request->getIPAddress();

            // Bug 1 fix: compare against session-stored math answer, not date()
            $correctAnswer = session()->get('seq-ans');
            session()->remove('seq-ans');
            if ($correctAnswer === null || (int)$sanswer !== (int)$correctAnswer) {
                // ✅ SECURITY: Record failed attempt (security question failed)
                \App\Filters\RateLimiting::recordAttempt($ipAddress, $username, false);
                
                session()->set('error', 'Security answer is not valid');
                return view(ADMIN_PATH . '/login-page');
            }

            $record = $this->homeModel->doLogin($username, $password);

            if ($record !== '0') {
                // ✅ SECURITY: Record successful login
                \App\Filters\RateLimiting::recordAttempt($ipAddress, $username, true);
                
                // ✅ SECURITY: Clear failed attempts for this IP
                \App\Filters\RateLimiting::clearAttempts($ipAddress);
                
                // ✅ SECURITY FIX 1.4: Remove password from session storage
                // Don't store the password hash in session files (security risk)
                unset($record->password);
                session()->set('fw', $record);

                // ✅ SECURITY FIX 1.6: Use secure random token for remember-me instead of MD5
                $tokenRaw = bin2hex(random_bytes(32));  // 64-char hex string
                $tokenHash = hash_hmac('sha256', $tokenRaw, config('Encryption')->key);
                
                $this->db->table('fw_users')
                         ->where('userId', $record->userId)
                         ->update(['hashToken' => $tokenHash]);

                // Bug 4 fix: chain cookies onto the redirect response so they are sent together
                return redirect()->to(base_url(ADMIN_PATH . '/dashboard'))
                    ->setCookie('rememberme', $tokenRaw, 2592000)
                    ->setCookie('loginUsername', $record->username, 2592000);
            }

            // ✅ SECURITY: Record failed login attempt
            \App\Filters\RateLimiting::recordAttempt($ipAddress, $username, false);
            
            // ✅ SECURITY: Show remaining attempts
            $remainingAttempts = 5 - \App\Filters\RateLimiting::getAttemptCount($ipAddress);
            $errorMsg = 'Username / Password Invalid';
            if ($remainingAttempts > 0 && $remainingAttempts <= 3) {
                $errorMsg .= " ($remainingAttempts attempts remaining before lockout)";
            }
            
            session()->set('error', $errorMsg);
            return view(ADMIN_PATH . '/login-page');
        }

        // GET request — show login form
        return view(ADMIN_PATH . '/login-page');
    }

    public function doLogout(): \CodeIgniter\HTTP\RedirectResponse
    {
        delete_cookie('rememberme');
        session()->remove('fw');
        session()->destroy();
        return redirect()->to(base_url(ADMIN_PATH . '/login'));
    }

    public function notfound(): string
    {
        return view('error-404');
    }

    public function noRecordFound(): string
    {
        return view('page-notfound');
    }

    public function dashboardStates(): \CodeIgniter\HTTP\Response
    {
        $posts    = $this->db->table('fw_posts')->select('postId')->get()->getNumRows();
        $visitors = $this->db->table('fw_post_view')->select('viewId')->get()->getNumRows();

        $pendingFileUpload = $this->homeModel->viewPostPendingLink();
        $failedPost        = $this->homeModel->viewFailePendingPosts();

        $firmwares = $this->db->table('fw_posts')->select('postId')->groupBy('version')->get()->getNumRows();
        $models    = $this->db->table('fw_posts')->select('postId')->groupBy('model')->get()->getNumRows();
        $downloads = $this->db->table('fw_posts')->selectSum('downloadCount', 'dcoun')->get()->getRow()->dcoun ?? 0;

        $data = [
            'totalFirmware'     => number_format($posts),
            'visitors'          => number_format($visitors),
            'pendingUploads'    => number_format($pendingFileUpload),
            'firmwares'         => number_format($firmwares),
            'models'            => number_format($models),
            'downloads'         => number_format($downloads),
            'failedPost'        => number_format($failedPost),
        ];

        return $this->response->setContentType('application/json')
                              ->setBody(json_encode($data));
    }

    public function sitemap(): \CodeIgniter\HTTP\Response
    {
        $urls = [];
        $item = new \stdClass();
        $item->loc = base_url(); $item->lastmod = date(DATE_ATOM, time());
        $item->changefreq = 'daily'; $item->priority = '1';
        $urls[] = $item;

        // ✅ SECURITY FIX: Only include Active posts in sitemap, exclude Draft/Inactive
        // Performance: Optimized via database index on postStatus, postId
        $record = $this->db->table('fw_posts')
                           ->where('postStatus', 'Active')
                           ->orderBy('postId', 'asc')
                           ->get()
                           ->getResult();
        
        $gi = 1; $linkCount = 1;

        foreach ($record as $rec) {
            if ($rec->postStatus === 'Active') {
                $gi++;
                if ($gi == SITEMAP_LIMIT) {
                    $item = new \stdClass();
                    $item->loc = base_url('sitemaps/sitemappage' . $linkCount);
                    $urls[] = $item; $gi = 1; $linkCount++;
                }
            }
        }
        if ($gi != '1') { $item = new \stdClass(); $item->loc = base_url('sitemaps/sitemappage' . $linkCount); $urls[] = $item; }

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><urlset/>');
        $xml->addAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        foreach ($urls as $url) {
            $child = $xml->addChild('url');
            $child->addChild('loc', $url->loc);
            if (isset($url->lastmod))    $child->addChild('lastmod',    $url->lastmod);
            if (isset($url->changefreq)) $child->addChild('changefreq', $url->changefreq);
            if (isset($url->priority))   $child->addChild('priority',   number_format($url->priority, 1));
        }
        return $this->response->setContentType('application/xml')->setBody($xml->asXml());
    }

    public function sitemapBlog(): \CodeIgniter\HTTP\Response
    {
        $urls = [];
        
        // Performance: Optimized via database indexes
        $record = $this->db->table('fw_blogs')->orderBy('postId', 'asc')->get();
        
        if ($record->getNumRows() > 0) {
            foreach ($record->getResult() as $rec) {
                if ($rec->postStatus === 'Active') {
                    $item = new \stdClass();
                    $item->loc = base_url('blog/' . $rec->postSlug);
                    $item->lastmod = date(DATE_ATOM, strtotime($rec->modifiedTime) + rand(0, 100));
                    $item->changefreq = 'monthly'; $item->priority = '0.9';
                    $urls[] = $item;
                }
            }
        }
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><urlset/>');
        $xml->addAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        foreach ($urls as $url) {
            $child = $xml->addChild('url'); $child->addChild('loc', $url->loc);
            if (isset($url->lastmod))    $child->addChild('lastmod',    $url->lastmod);
            if (isset($url->changefreq)) $child->addChild('changefreq', $url->changefreq);
            if (isset($url->priority))   $child->addChild('priority',   number_format($url->priority, 1));
        }
        return $this->response->setContentType('application/xml')->setBody($xml->asXml());
    }

    public function sitemapPage(): \CodeIgniter\HTTP\Response
    {
        $urls = []; $limit = SITEMAP_LIMIT;
        $page = str_replace(['sitemappage', '.xml'], '', $this->request->getUri()->getSegment(2));
        $page = $page - 1; $pageRecord = $page * $limit;

        // ✅ SECURITY FIX: Only include Active posts in sitemap, exclude Draft/Inactive
        // Performance: Optimized via database indexes on postStatus, postId
        $record = $this->db->table('fw_posts')
                           ->where('postStatus', 'Active')
                           ->orderBy('postId', 'asc')
                           ->limit($limit, $pageRecord)
                           ->get();
        
        if ($record->getNumRows() > 0) {
            foreach ($record->getResult() as $rec) {
                if ($rec->postStatus === 'Active') {
                    $item = new \stdClass();
                    $item->loc = postUrl($rec);
                    $item->lastmod = date(DATE_ATOM, strtotime($rec->modifiedTime) + rand(0, 100));
                    $item->changefreq = 'daily'; $item->priority = '0.9';
                    $urls[] = $item;
                }
            }
        }
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><urlset/>');
        $xml->addAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $alreadyIn = [];
        foreach ($urls as $url) {
            if (!in_array($url->loc, $alreadyIn)) {
                $child = $xml->addChild('url'); $child->addChild('loc', $url->loc);
                if (isset($url->lastmod))    $child->addChild('lastmod',    $url->lastmod);
                if (isset($url->changefreq)) $child->addChild('changefreq', $url->changefreq);
                if (isset($url->priority))   $child->addChild('priority',   number_format($url->priority, 1));
                $alreadyIn[] = $url->loc;
            }
        }
        return $this->response->setContentType('application/xml')->setBody($xml->asXml());
    }

    public function contactUs(): void
    {
        if (!$this->request->isAJAX()) exit('Directory access is forbidden');
        
        // ✅ SECURITY: Sanitize all inputs
        $name        = InputSanitizer::sanitizeString($this->request->getPost('name'));
        $email       = InputSanitizer::sanitizeEmail($this->request->getPost('email'));
        $website     = InputSanitizer::sanitizeUrl($this->request->getPost('website'));
        $description = InputSanitizer::sanitizeString($this->request->getPost('description'));
        $sanswer     = InputSanitizer::sanitizeString($this->request->getPost('sanswer'));
        $form_token  = base64_decode(trim($this->request->getPost('form_token')));
        
        $securityWords = ['CupCake', 'auToMobile', 'Samsung', 'Firmware', 'bOOt', 'MoBile'];
        
        // Validate required fields
        if (!InputSanitizer::validateRequired($name)) {
            exit('Please enter your name');
        }
        
        if (!InputSanitizer::validateEmail($email)) {
            exit('Please enter valid email address');
        }
        
        if (!InputSanitizer::validateRequired($description)) {
            exit('Please enter description');
        }
        
        if ($website !== '') {
            if (!InputSanitizer::validateUrl('https://' . $website)) {
                if (strpos($website, 'https://') === false && strpos($website, 'http://') === false) {
                    $website = 'https://' . $website;
                }
            }
            if (!InputSanitizer::validateUrl($website)) {
                exit('Please enter valid website address');
            }
        }
        
        if (!in_array($sanswer, $securityWords) || $form_token !== $sanswer) {
            exit('Security question answer is not valid');
        }
        
        $this->db->table('fw_contact_us')->insert([
            'fromName'    => $name,
            'fromEmail'   => $email,
            'website'     => $website,
            'description' => $description,
            'createdTime' => date('Y-m-d H:i:s'),
            'ipAddress'   => $this->request->getIPAddress(),
        ]);
        
        exit('success');
    }

    public function stawain(): void {}

    public function authDrive(): \CodeIgniter\HTTP\RedirectResponse|null
    {
        $filePath = RESOURCE_PATH . 'auth-resource.info';
        $client_id = '323365054618-1hudg6ejt8k33re2kbbaurvdvlc0udo1.apps.googleusercontent.com';
        $client_secret = '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';
        $redirect_uri = 'https://samfware.com/home/authDrive';
        if ($this->request->getGet('code')) {
            if (!file_exists($filePath)) { $h = @fopen($filePath, 'w'); @fclose($h); }
            $oauth2 = ['client_id' => $client_id, 'client_secret' => $client_secret];
            try {
                $provider = new \League\OAuth2\Client\Provider\GenericProvider([
                    'clientId' => $client_id, 'clientSecret' => $client_secret, 'redirectUri' => $redirect_uri,
                    'urlAuthorize' => 'https://www.googleapis.com/oauth2/v4/authorize',
                    'urlAccessToken' => 'https://www.googleapis.com/oauth2/v4/token', 'urlResourceOwnerDetails' => '',
                ]);
                $accessToken = $provider->getAccessToken('authorization_code', ['code' => $this->request->getGet('code')]);
                $oauth2['hasError'] = 'false'; $oauth2['access_token'] = $accessToken->getToken();
                $oauth2['expires_in'] = $accessToken->getExpires(); $oauth2['timeIn'] = time();
                $oauth2['refresh_token'] = $accessToken->getRefreshToken();
            } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) { $oauth2['hasError'] = 'true'; }
            @file_put_contents($filePath, json_encode($oauth2));
            return null;
        } else {
            return redirect()->to('https://accounts.google.com/o/oauth2/v2/auth/oauthchooseaccount?redirect_uri=' . $redirect_uri . '&prompt=consent&response_type=code&client_id=' . $client_id . '&scope=https://www.googleapis.com/auth/drive&access_type=offline');
        }
    }

    public function ping(): void
    {
        $oauth2 = @json_decode(@file_get_contents(RESOURCE_PATH . 'auth-resource.info'), true);
        if (!$oauth2 || !isset($oauth2['expires_in']) || !isset($oauth2['timeIn'])) { echo 'false'; return; }
        $timeDiff = $oauth2['expires_in'] - $oauth2['timeIn'];
        $return = 'false';
        if (time() > ($oauth2['timeIn'] + $timeDiff)) { $this->refreshAuth(); $return = 'true'; }
        echo $return;
    }

    public function refreshAuth(): array
    {
        $filePath = RESOURCE_PATH . 'auth-resource.info';
        $oauth2 = @json_decode(@file_get_contents($filePath), true);
        if (!$oauth2) return [];
        try {
            $provider = new \League\OAuth2\Client\Provider\GenericProvider([
                'clientId' => $oauth2['client_id'] ?? '', 'clientSecret' => $oauth2['client_secret'] ?? '',
                'redirectUri' => 'https://samfware.com/home/authDrive',
                'urlAuthorize' => 'https://www.googleapis.com/oauth2/v4/authorize',
                'urlAccessToken' => 'https://www.googleapis.com/oauth2/v4/token', 'urlResourceOwnerDetails' => '',
            ]);
            $accessToken = $provider->getAccessToken('refresh_token', ['refresh_token' => $oauth2['refresh_token'] ?? '']);
            $oauth2['hasError'] = 'false'; $oauth2['access_token'] = $accessToken->getToken();
            $oauth2['expires_in'] = $accessToken->getExpires(); $oauth2['timeIn'] = time();
            $oauth2['refresh_token'] = $accessToken->getRefreshToken() ?: ($oauth2['refresh_token'] ?? '');
        } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) { $oauth2['hasError'] = 'true'; }
        @file_put_contents($filePath, json_encode($oauth2));
        return $oauth2;
    }

    public function downloadnow(): void
    {
        $postId = trim($this->request->getGet('id') ?? $this->request->getPost('id') ?? '');
        $token  = trim($this->request->getGet('token') ?? $this->request->getPost('token') ?? '');
        $this->db->table('fw_posts')->where('postId', $postId)->set('downloadCount', 'downloadCount + 1', false)->update();
        $response = @json_decode(@file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=6LfFIOggAAAAANTrZmEG8MiC_cUvZLrkwhV60-xd&response=' . $token . '&remoteip=' . $this->request->getIPAddress()), true);
        if (empty($response['success'])) {
            $dataArr = ['code' => 604];
        } else {
            $postd = $this->homeModel->getSinglePost((int) $postId);
            $code = 404; $fileName = ''; $url = '';
            if ($postd !== '0') {
                $code = 200;
                $fileName = 'firmware-' . $postd->model . '-' . $postd->country . '-' . $postd->version . '.zip';
                $downloadButton = @json_decode($postd->downloadButton, true);
                $url = $downloadButton['buttonUrl'] ?? '';
                if (($url === '' || $url === null) && $postd->externalFileLink !== '') $url = $postd->externalFileLink;
            }
            $dataArr = ['code' => $code, 'url' => $url, 'filename' => $fileName];
        }
        $this->response->setContentType('application/json');
        echo json_encode($dataArr);
    }

    public function accessLink(): void
    {
        $data = @file_get_contents('https://samfware.com/sitemaps');
        $links = []; $count = preg_match_all('@<loc>(.+?)<\/loc>@', $data, $matches);
        for ($i = 0; $i < $count; ++$i) $links[] = $matches[0][$i];
        if (is_array($links) && count($links) > 0) {
            $random_keys = array_rand($links, min(200, count($links)));
            foreach ($random_keys as $key) { try { @file_get_contents(str_replace(['<loc>', '</loc>'], '', $links[$key])); } catch (\Exception $e) {} }
        }
    }
}