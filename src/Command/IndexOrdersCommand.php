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
                'body' => 'mode=raw&query=' . urlencode('CREATE TABLE IF NOT EXISTS orders (id BIGINT PRIMARY KEY, search_text TEXT) rt'),
            ]);

            $client->request('POST', sprintf('http://%s:%s/cli', $host, $port), [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'body' => 'mode=raw&query=' . urlencode('TRUNCATE TABLE orders'),
            ]);

            foreach ($rows as $row) {
                $searchText = sprintf('%d %s %s', (int) $row['id'], $row['created_at'], $row['amount']);
                $client->request('POST', sprintf('http://%s:%s/cli', $host, $port), [
                    'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                    'body' => 'mode=raw&query=' . urlencode(sprintf(
                        "INSERT INTO orders (id, search_text) VALUES (%d, '%s')",
                        (int) $row['id'],
                        addslashes($searchText)
                    )),
                ]);
            }

        $output->writeln('Indexed orders into Manticore');
        return Command::SUCCESS;
    }
}
