<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpClient\HttpClient;

#[AsCommand(name: 'app:manticore:index-orders')]
final class IndexOrdersCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rows = $this->connection->fetchAllAssociative('SELECT id, created_at, amount FROM `order`');
        $client = HttpClient::create(['timeout' => 5]);
        $host = getenv('MANTICORE_HOST') ?: 'manticore';
        $port = getenv('MANTICORE_PORT') ?: '9308';

        $client->request('POST', sprintf('http://%s:%s/cli', $host, $port), [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body' => 'mode=raw&query=' . urlencode('CREATE TABLE IF NOT EXISTS orders (id BIGINT, created_at STRING, amount FLOAT)'),
        ]);

        foreach ($rows as $row) {
            $client->request('POST', sprintf('http://%s:%s/cli', $host, $port), [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'body' => 'mode=raw&query=' . urlencode(sprintf(
                    "REPLACE INTO orders (id, created_at, amount) VALUES (%d, '%s', %f)",
                    (int) $row['id'],
                    $row['created_at'],
                    (float) $row['amount']
                )),
            ]);
        }

        $output->writeln('Indexed orders into Manticore');
        return Command::SUCCESS;
    }
}
