<?php

declare(strict_types=1);

namespace at\fanninger\WebtreesModules\SimpleAutoLogin\Modules;

use Exception;
use Fisharebest\Webtrees\Http\RequestHandlers\LoginPage;
use Fisharebest\Webtrees\Http\RequestHandlers\HomePage;
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Carbon;
use Fisharebest\Webtrees\Contracts\UserInterface;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Log;
use Fisharebest\Webtrees\Services\UserService;
use Fisharebest\Webtrees\Services\TreeService;
use Fisharebest\Webtrees\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function route;

class SimpleAutoLoginPage extends LoginPage
{
	
    /** @var UserService */
    private $user_service;

    /**
     * LoginController constructor.
     *
     * @param UserService    $user_service
     */
    public function __construct(TreeService $tree_service, UserService $user_service)
    {
    	parent::__construct($tree_service);
        $this->user_service = $user_service;
    }
	
	public function handle(ServerRequestInterface $request): ResponseInterface
	{	
        $tree = $request->getAttribute('tree');

        $params = (array) $request->getParsedBody();
		$url = array_key_exists('url', $params) ? $params['url'] : route(HomePage::class);
		
		// Need to set the trusted server parameter in the config.ini.pph of webtrees
		// EXAMPLE: trusted_header_authenticated_user="REMOTE_USER"
		// oauth2-proxy: HTTP_X_FORWARDED_PREFERRED_USERNAME = USERNAME
		// Apache mod_ssl: SSL_CLIENT_S_DN_CN = USERNAME
	    // general: REMOTE_USER = USERNAME
		
		$trusted_header = $request->getAttribute('trusted_header_authenticated_user');
		if (isset($trusted_header) && $trusted_header !== null && $trusted_header !== '') {
			$server_params = (array) $request->getServerParams($trusted_header);
			if (array_key_exists($trusted_header, $server_params)) {
				$username = $server_params[$trusted_header];
			}
		}

		if (isset($username) && $username !== null && $username !== '') {  
			$user = $this->user_service->findByIdentifier($username);
		}
		
		if (isset($user) && $user !== null && $user !== '') {  
			try {
		        if ($user->getPreference(UserInterface::PREF_IS_EMAIL_VERIFIED) !== '1') {
		            Log::addAuthenticationLog('Login failed (not verified by user): ' . $username);
		            throw new Exception(I18N::translate('This account has not been verified. Please check your email for a verification message.'));
		        }
		
		        if ($user->getPreference(UserInterface::PREF_IS_ACCOUNT_APPROVED) !== '1') {
		            Log::addAuthenticationLog('Login failed (not approved by admin): ' . $username);
		            throw new Exception(I18N::translate('This account has not been approved. Please wait for an administrator to approve it.'));
		        }

		        Auth::login($user);
		        Log::addAuthenticationLog('Login: ' . Auth::user()->userName() . '/' . Auth::user()->realName());
		        Auth::user()->setPreference(UserInterface::PREF_TIMESTAMP_ACTIVE, (string) time());
		
		        Session::put('language', Auth::user()->getPreference(UserInterface::PREF_LANGUAGE));
		        Session::put('theme', Auth::user()->getPreference(UserInterface::PREF_THEME));
		        I18N::init(Auth::user()->getPreference(UserInterface::PREF_LANGUAGE));

            	return redirect($url);
	        } catch (Exception $ex) {
	            // Failed to log in.
	            FlashMessages::addMessage($ex->getMessage(), 'danger');
	
	            return redirect(route(LoginPage::class, [
	                'tree'     => $tree instanceof Tree ? $tree->name() : null,
	                'username' => $username,
	                'url'      => $url,
	            ]));
	        }
		}else{
			return parent::handle($request);
		}
	}
}

