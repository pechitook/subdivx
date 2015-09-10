<?php namespace P4bloch\Subdivx;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\InputArgument;
use GuzzleHttp\Client;

class Download extends Command
{

	protected $helper;
	protected $input;
	protected $output;

	public function configure()
	{
		$this->setName('download')
			->addArgument(
				'query',
				InputArgument::OPTIONAL,
				'El subtítulo que quieras bajar')
			->addArgument(
				'version',
				InputArgument::OPTIONAL,
				'Filtra los resultados de la búsqueda (versión, grupo, etc)');
	}

	/**
	 * Execute the command. Think of this method as the controller
	 * for this specific command, as it triggers everything else.
	 *
	 * @param \Symfony\Component\Console\Input\InputInterface $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 * @return void
	 */
	public function execute($input, $output)
	{
		// prepare context
		$this->client = new Client;
		$this->input = $input;
		$this->output = $output;
		$this->helper = $this->getHelper('question');

		if (!$query = $this->input->getArgument('query'))
		{
			$question = new Question('¿Qué subtítulo querés buscar?: ');
			$query = $this->helper->ask($this->input, $this->output, $question);
		}

		$html = $this->getPageFromQuery($query);

		$subtitles = $this->getSubtitlesFromHtml($html);

		if ($version = $this->input->getArgument('version'))
		{
			$subtitles = $this->filter($subtitles, $version);
		}

		if (!$subtitles)
		{
			$this->output->writeln("<error>La búsqueda no devolvió ningún resultado.</error>");
			exit();
		}

		$this->displaySubtitlesToUser($subtitles);

		$selectedIndex = $this->askWhichOneToDownload($subtitles);
		$selectedSubtitle = $subtitles[$selectedIndex];

		$this->download($selectedSubtitle);

		$this->output->writeln('<info>Listo!</info>');
	}

	/**
	 * Returns an array with information and download
	 * link for each subtitle on the given HTML
	 *
	 * @param  string
	 * @return array
	 */
	private function getSubtitlesFromHtml($html)
	{
		$crawler = new \Symfony\Component\DomCrawler\Crawler($html);

		$subs = array();
		$allSubsInPage = $crawler->filterXPath('//*[@id="buscador_detalle"]');
		$allTitlesInPage = $crawler->filterXPath('//*[@id="menu_titulo_buscador"]');

		$allTitlesInPage->filterXPath('//a')->each(function ($node, $i) use (&$subs)
		{
			$subs[$i + 1]['sub_link'] = $node->attr('href');
		});

		// get description of each sub
		$allSubsInPage->filterXPath('//*[@id="buscador_detalle_sub"]/text()')->each(function ($node, $i) use (&$subs)
		{
			$subs[$i + 1]['description'] = $node->text();
		});

		return $subs;
	}

	/**
	 * Show a list of the subtitles to the output.
	 *
	 * @param $subs
	 */
	private function displaySubtitlesToUser($subs)
	{
		foreach ($subs as $key => $sub)
		{
			$this->output->writeln("<comment>Option [{$key}]</comment>");
			$this->output->writeln("<info>{$sub['description']}</info>");
			echo "\n";
		}
	}

	/**
	 * Ask the user interactively what he/she wants to search for.
	 *
	 * @param array $subs
	 * @return string
	 */
	private function askWhichOneToDownload($subs)
	{
		$indexes = array_keys($subs);
		$question = new Question('<info>¿Qué versión querés bajar? [' . min($indexes) . '-' . max($indexes) . '] </info>');

		return $this->helper->ask($this->input, $this->output, $question);
	}

	/**
	 * Given a subtitle, download and decompress it to the current folder
	 *
	 * @param $subtitle
	 */
	private function download($subtitle)
	{
		$link = $this->getDownloadLink($subtitle['sub_link']);

		$filename = $this->getRandomString();
		$tmpPath = getcwd();

		$res = $this->client->get($link, ['save_to' => $tmpPath . '/' . $filename]);
		var_dump($res->getEffectiveUrl());
		$fileext = $this->getFileExtension($res->getEffectiveUrl());

		$fullPath = $this->addExtensionToFile($tmpPath, $filename, $fileext);
		$this->decompress($fullPath, $fileext);

		// delete the compressed file
		unlink($fullPath);
	}

	/**
	 * Given a string to a full resource, it returns the file extension
	 *
	 * @param string $url
	 * @return string
	 */
	private function getFileExtension($url)
	{
		$info = new \SplFileInfo(basename($url));

		return $info->getExtension();
	}

	/**
	 * Returns a random string.
	 *
	 * @return string
	 */
	private function getRandomString()
	{
		return md5(mt_rand());
	}

	/**
	 * Returns the full path to the downloaded file, with
	 * the original extension it had when downloading.
	 *
	 * @param string $fullpath
	 * @param string $filename
	 * @param string $fileext
	 * @return string
	 */
	private function addExtensionToFile($fullpath, $filename, $fileext)
	{
		$newPath = $fullpath . '/' . $filename . '.' . $fileext;
		rename($fullpath . '/' . $filename, $newPath);

		return $newPath;
	}

	/**
	 * Decompress the downloaded compressed file into the current directory
	 *
	 * @param $fullPath
	 * @param $ext
	 */
	private function decompress($fullPath, $ext)
	{
		if (strtolower($ext) == 'rar')
		{
			// not cool to depend upon unrar, needs more work
			exec('unrar e ' . escapeshellarg($fullPath));
		}
		if (strtolower($ext) == 'zip')
		{
			// not *that* cool to depend upon unzip, probably needs more work
			exec('unzip ' . escapeshellarg($fullPath));
		}
	}

	/**
	 * Gets the HTML from the subtitle's download page, ordered by last added.
	 *
	 * @param string $query The query to look for
	 * @return string
	 */
	private function getPageFromQuery($query)
	{
		$url = "http://www.subdivx.com/index.php?accion=5&masdesc=&buscar=$query&oxfecha=2";
		$res = $this->client->get($url);

		return (string) $res->getBody();
	}

	/**
	 * Filters through given subtitles array and returns only
	 * those whose description matches with $version.
	 *
	 * @param $subtitles
	 * @param $version
	 * @return array
	 */
	private function filter($subtitles, $version)
	{
		$filteredSubtitles = [];

		foreach ($subtitles as $subtitle)
		{
			if (preg_match("|$version|", $subtitle['description']))
			{
				$filteredSubtitles[] = $subtitle;
			}
		}

		return $filteredSubtitles;
	}

	private function getDownloadLink($sub_link)	{
		$res = $this->client->get($sub_link);
		$body = (string) $res->getBody();

		preg_match_all('|a rel="nofollow" class="detalle_link" href="(.*?)"><b>Bajar<\/b>|', $body, $link);
		$res = $this->client->get($link[1][0]);
		$body = (string) $res->getBody();
		preg_match('|document.location.href=\'(.*?)\'|', $body, $res);

		return $res[1];
	}

}
