<?php declare(strict_types=1);
namespace Boxalino\IntelligenceFramework\Service\Test;

use Boxalino\IntelligenceFramework\Service\Test\Api\RestService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ApiCommand extends Command
{
    protected static $defaultName = 'boxalino:api:test';

    protected $restService;

    public function __construct(
        RestService $restService
    ){
        parent::__construct();
        $this->restService = $restService;
    }

    protected function configure()
    {
        $this->setDescription("Boxalino API Rest Service Test.");
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $rustart = getrusage();
        $this->restService->request();
        $ru = getrusage();
        $output->writeln("This process used " . $this->rutime($ru, $rustart, "utime") . " ms for its computations");
        $output->writeln("It spent " . $this->rutime($ru, $rustart, "stime") . " ms in system calls");

        return 0;
    }

    protected function rutime($ru, $rus, $index) {
        return ($ru["ru_$index.tv_sec"]*1000 + intval($ru["ru_$index.tv_usec"]/1000))
            -  ($rus["ru_$index.tv_sec"]*1000 + intval($rus["ru_$index.tv_usec"]/1000));
    }

}
