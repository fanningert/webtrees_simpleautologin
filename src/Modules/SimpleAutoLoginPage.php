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
		
		$server_params = (array) $request->getServerParams();
		
		if (array_key_exists('REMOTE_USER', $server_params) && $server_params['REMOTE_USER'] !== '') {
			$username = $server_params['REMOTE_USER'];
		}elseif (array_key_exists('HTTP_X_FORWARDED_PREFERRED_USERNAME', $server_params) && $server_params['HTTP_X_FORWARDED_PREFERRED_USERNAME'] !== '') {
			$username = $server_params['HTTP_X_FORWARDED_PREFERRED_USERNAME'];	
		}
		
		if ($username !== '') {  
			$user = $this->user_service->findByIdentifier($username);
		}
		
		if ($user !== null) {  
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
		        Auth::user()->setPreference(UserInterface::PREF_TIMESTAMP_ACTIVE, (string) Carbon::now()->unix());
		
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