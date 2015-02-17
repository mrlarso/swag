<?php

	require_once __DIR__."/../utils/FileUtil.php";

	/**
	 * Controller class.
	 */
	class MainController extends WebController {

		private $config;

		/**
		 * Constructor.
		 */
		function MainController() {
			session_start();

			$this->method("main");
			$this->method("logout");
			$this->method("showmap")->args("filename");
			$this->method("getmap")->args("filename");
			$this->method("login")->args("username","password");
			$this->setDefaultMethod("main");

			$this->loadConfig();
		}

		/**
		 * Load configuration.
		 */
		private function loadConfig() {
			$configFileName=__DIR__."/../../config.php";

			if (!file_exists($configFileName))
				exit("Config file does not exists, looking for: ".realpath(__DIR__."/../../")."/config.php");

			require_once($configFileName);

			$this->config=array();

			$this->config["xapiEndpoint"]=$xapiEndpoint;
			$this->config["xapiUsername"]=$xapiUsername;
			$this->config["xapiPassword"]=$xapiPassword;
			$this->config["actorDomain"]=$actorDomain;

			if (isset($useProxy))
				$this->config["useProxy"]=$useProxy;
		}

		/**
		 * The main page.
		 * Show list of swagmaps or the login screen.
		 */
		function main() {
			if (!isset($_SESSION["username"])) {
				$t=new Template(__DIR__."/../templates/login.php");
				$t->set("message",NULL);
				$this->showContent($t);
				return;
			}

			$this->showSwagList();
		}

		/**
		 * Login the user.
		 */
		function login($username, $password) {
			$error=NULL;
			$res=pam_auth($username,$password,$error);

			if (!$res) {
				$t=new Template(__DIR__."/../templates/login.php");
				$t->set("message",$error);
				$this->showContent($t);
				return;
			}

			$_SESSION["username"]=$username;
			$this->redirect();
		}

		/**
		 * Log out the current user.
		 */
		function logout() {
			unset($_SESSION["username"]);
			$this->redirect();
		}

		/**
		 * Show list of swagmaps.
		 */
		function showSwagList() {
			$swagmapdir=__DIR__."/../../extern/swagmaps";
			$swagmaps=array();
			$swagmapfiles=FileUtil::findFilesWithExt($swagmapdir,"json");

			foreach ($swagmapfiles as $swagmapfile) {
				$swagmap=json_decode(file_get_contents($swagmapdir."/".$swagmapfile),TRUE);
				$swagmap["filename"]=$swagmapfile;

				if (!isset($swagmap["title"]))
					$swagmap["title"]=$swagmapfile;

				$swagmaps[]=$swagmap;
			}

			$t=new Template(__DIR__."/../templates/swaglist.php");
			$t->set("swagmaps",$swagmaps);
			$this->showContent($t);
		}

		/**
		 * Show content with header and footer.
		 */
		function showContent($c) {
			$t=new Template(__DIR__."/../templates/base.php");
			$t->set("content",$c->render());
			$t->set("baseUrl",RewriteUtil::getBaseUrl());
			$t->show();
		}

		/**
		 * Show a swagmap.
		 */
		function showmap($filename) {
			$this->requireLogin();

			$t=new Template(__DIR__."/../templates/swagmap.php");
			$t->set("baseUrl",RewriteUtil::getBaseUrl());
			$t->set("mapUrl",RewriteUtil::getBaseUrl()."/main/getmap?filename=".urlencode($filename));
			$t->set("actorEmail",$_SESSION["username"]."@".$this->config["actorDomain"]);

			if (isset($this->config["useProxy"]) && $this->config["useProxy"])
				$t->set("xapiEndpoint",RewriteUtil::getBaseUrl()."xapiproxy");

			else
				$t->set("xapiEndpoint",$this->config["xapiEndpoint"]);

			$t->set("xapiUsername",$this->config["xapiUsername"]);
			$t->set("xapiPassword",$this->config["xapiPassword"]);
			$t->show();
		}

		/**
		 * Get data for a swagmap.
		 */
		function getmap($filename) {
			$swagmapdir=__DIR__."/../../extern/swagmaps";

			echo file_get_contents($swagmapdir."/".$filename);
		}

		/**
		 * Redirect to the frontpage if not logged in.
		 */
		protected function requireLogin() {
			if (!isset($_SESSION["username"]))
				$this->redirect();
		}

		/**
		 * Redirect to a page.
		 */
		protected function redirect($page=NULL) {
			$url=RewriteUtil::getBaseUrl();

			if ($page)
				$url.="/".$page;

			header("Location: ".$url);
			exit();
		}
	}