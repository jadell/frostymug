<?php
namespace Beerme\Controller;

use Silex\Application,
    Silex\Provider\SessionServiceProvider as Session,
	Symfony\Component\HttpFoundation\Request,
    Beerme\UserStore,
	LightOpenID,
	Swift_Message as Message;

class WebUi
{
	/**
	 * Register this controller with the application
	 *
	 * @param Application
	 */
	public static function register(Application $app)
	{
		$app->register(new Session());
		$app['userStore'] = $app->share(function($app) {
			return new UserStore($app['neo4j']);
		});

		$app->get('/', function () use ($app) {
			return WebUi::render($app['templateDir'].'/play.php', array(
				'user' => $app['session']->get('user'),
				'lastSearch' => $app['session']->get('lastSearch'),
				'flashSuccess' => $app['session']->getFlash('flashSuccess', null),
			));
		});

		$app->post('/login', function (Request $request) use ($app) {
			$identifier = $request->get('openid_identifier');
			try {
				$app['session']->set('loginBeerId', $request->get('beer_id'));
				$app['session']->set('loginRating', $request->get('rating'));

				$openId = new LightOpenID($_SERVER['HTTP_HOST']);
				$openId->identity = $identifier;
				$openId->required = array('contact/email');

				return $app->redirect($openId->authUrl());
			} catch (ErrorException $e) {
				die($e->getMessage());
			}
		});

		$app->get('/login', function () use ($app) {
			try {
				$openId = new LightOpenID($_SERVER['HTTP_HOST']);
				if ($openId->mode == 'cancel') {
					return $app->redirect('/');
				} else if (!$openId->validate()) {
					die('Invalid login');
				}

				$attributes = $openId->getAttributes();
				if (empty($attributes['contact/email'])) {
					die('Unable to determine email address');
				}

				$userStore = $app['userStore'];
				$user = $userStore->createUser($attributes['contact/email']);
				if ($user) {
					$app['session']->start();
					$app['session']->set('user', $user->toApi());

					// Did we log in from a rating?
					$beerId = $app['session']->get('loginBeerId');
					$rating = $app['session']->get('loginRating');
					if ($beerId) {
						$beer = $app['beerStore']->getBeer($beerId);
						$app['beerStore']->rateBeer($user, $beer, $rating);
					}

					return $app->redirect('/');
				}

			} catch (ErrorException $e) {
				die($e->getMessage());
			}
		});

		$app->get('/logout', function () use ($app) {
			$app['session']->set('user', null);
			$app['session']->invalidate();
			$app['session']->clear();
			return $app->redirect('/');
		});

		$app->post('/feedback', function (Request $request) use ($app) {
			$loggedInUser = $app['session']->get('user');
			$realEmail = $loggedInUser ? $loggedInUser['email'] : 'unknown';
			$givenEmail = $request->get('email', '');
			$how = $request->get('how', '');
			$features = implode(', ', $request->get('feedback-feature', array()));
			$comments = $request->get('comments', '');
			$body =  "Feedback from: $realEmail"."\n\n"
			       . "Given email: $givenEmail"."\n\n"
			       . "How did they find FrostyMug: $how"."\n\n"
			       . "Most desired features: $features"."\n\n"
			       . "Comments: $comments";
			$message = Message::newInstance()
				->setFrom('frostymug.beer@gmail.com')
				->setTo('frostymug.beer@gmail.com')
				->setSubject('FrostyMug Feedback')
				->setBody($body);
			if ($givenEmail) {
				$message->setSender($givenEmail)
					->setReplyTo($givenEmail);
			}
			$app['mailer']->send($message);
			$app['session']->setFlash('flashSuccess', '<strong>Thank you!</strong> Your feedback is appreciated.');
			return $app->redirect('/');
		});
	}

	/**
	 * Render the given template
	 *
	 * @param string $templateFile
	 * @param array $vars
	 * @return string
	 */
	public function render($templateFile, $vars=array())
	{
		extract($vars);
		ob_start();
		require($templateFile);
		return ob_get_clean();
	}
}