# Auth for Minibase

Auth is a big and customizable plugin for [Minibase](https://github.com/peec/minibase) framework. It's for PHP 5.4+. If you need authentication 
for you app look no further. This plugin is cuztomizable, such as the [View customzation](docs/customize_views.md).


## Supported authentication mechanisms:

- Normal email/password auth.
- Facebook Oauth login.

## Plugin requirements

This plugin requires some plugins to be installed. The most important plugin is twig, the twig plugin overrides the default php view handeling of minibase.

- [Doctrine plugin](https://github.com/peec/minibase-plugin-doctrine): ORM
- [Twig plugin](https://github.com/peec/minibase-plugin-twig): Templating engine.
- [Csrf plugin](https://github.com/peec/minibase-plugin-csrfprotection): For security



## Install

This plugin must be initialized before Doctrine/Twig plugin.


### Sample integration with JSON:


Add the plugins like so in your `app.json` file.

```json
	"plugins": [
		{
			"name": "Pkj/Minibase/Plugin/AuthPlugin/AuthPlugin",
			"config": {
				"providers": {
					"facebook": {
						"appId": "APP_ID",
						"secret": "FACEBOOK_SECRET_KEY"
					}
				}
			}
		},
		{
			"name": "Pkj/Minibase/Plugin/TwigPlugin/TwigPlugin",
			"config": {
				
			}
		},
		{
			"name":"Pkj/Minibase/Plugin/Csrf/CsrfPlugin"
		},
		{
			"name": "Pkj/Minibase/Plugin/DoctrinePlugin/DoctrinePlugin",
			"config": {
				"metadata": "annotation",
				"entityDirs": ["${APP_DIR}/models/"],
				"proxyDir": "${APP_DIR}/cache/proxies",
				"connection": {
					"driver": "pdo_sqlite",
					"path": "${APP_DIR}/cache/db.sqlite"
				}
			}
		}
	]
```


### Update your database schema.

This plugin introduces some new database tables (for users).

Note, if you already have doctrine models in your app use `orm:schema-tool:update --dump-sql` and import it in your current database.

```bash
php cli.php orm:schema-tool:create 
```


### The routes


#### /login

View used in `AuthPlugin/login.html`, override this in your view folder to add your own login html code.


#### /register

Gives users the possiblity to register. 


#### /user/settings

The control panel for the users settings. The user may change his password from this url.



## Secure your controller methods.


This plugin introduces new annotations that you can put on your controllers or controller methods.

`use Pkj\Minibase\Plugin\AuthPlugin\Annotation as Restrict;`



#### @Restrict\Authenticated

The user must be authenticated, if not the view `AuthPlugin/must_authenticate.html` is displayed.


#### @Restrict\NotAuthenticated

The user must NOT be authenticated, if this fails the view `AuthPlugin/must_not_authenticate.html` is displayed.

#### Annotation parameters


The `redirect` parameter (eg. `@Restrict\Authenticate(redirect="MyController.heyloginnowplease")` will redirect the user instead 
of showing the `AuthPlugin/must_authenticate.html`.




