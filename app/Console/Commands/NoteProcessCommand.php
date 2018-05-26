<?php

namespace App\Console\Commands;

use App\Model\Service\Eloquent\EloquentNoteService;
use App\Model\Service\Eloquent\EloquentSpentService;
use App\Providers\AppServiceProvider;
use Illuminate\Console\Command;

class NoteProcessCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'note:process {--I|issue_id= : Issue id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse comments and store spent time into database';

    /**
     * @var EloquentNoteService
     */
    protected $noteService;

    /**
     * @var EloquentSpentService
     */
    protected $spentService;

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     * @throws \App\Model\Service\ServiceException
     */
    public function handle()
    {
        $this->noteService = app(AppServiceProvider::ELOQUENT_NOTE_SERVICE);
        $this->spentService = app(AppServiceProvider::ELOQUENT_SPENT_SERVICE);

        $parameters = [
            'issue_id' => $this->option('issue_id'),
            'order' => 'id',
            'orderDirection' => 'asc',
        ];
        $list = $this->noteService->getCompleteList($parameters);
        $spentList = $this->noteService->parseSpentTime($list);

        // Print list
        $headers = [
            'gitlab_created_at',
            'hours',
            'description',
        ];
        $data = [];
        foreach ($spentList as $item) {
            $row = [];
            foreach ($headers as $header) {
                $row[$header] = $item[$header];
            }
            $data[] = $row;
        }
        $this->table($headers, $data);

        $this->spentService->storeList($spentList);
        $this->info('Data stored in database');
    }

    protected function getUrlParameters(): array
    {
        return [];
    }
}