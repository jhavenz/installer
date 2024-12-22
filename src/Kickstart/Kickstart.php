<?php

declare(strict_types=1);

namespace Laravel\Installer\Console\Kickstart;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Pluralizer;
use RuntimeException;
use Symfony\Component\Finder\Finder;

use function Illuminate\Filesystem\join_paths;
use function str;

class Kickstart
{
    /**
     * @var Filesystem
     */
    public $fs;

    /**
     * @var Path
     */
    public $path;

    /**
     * @var Draft
     */
    public $draft;

    /**
     * @var bool
     */
    public $isUsingTeams;

    public function __construct(
        string $projectPath,
        string $template,
        bool $usingJetstreamTeams,
        Filesystem $fs = new Filesystem()
    ) {
        $this->fs = $fs;
        $this->path = new Path($projectPath);
        $this->isUsingTeams = $usingJetstreamTeams;
        $this->draft = new Draft($this->path, $template);
    }

    /**
     * @return void
     */
    public function copyDraftStubToProject()
    {
        $from = $this->draft->stub($this->isUsingTeams);

        $to = $this->draft->filePath();

        $this->fs->copy($from, $to);
    }

    /**
     * @return void
     * @throws FileNotFoundException
     */
    public function addTemplateSeederToProject()
    {
        if (!$this->draft->existsInProject()) {
            throw new RuntimeException('The draft file does not exist in project');
        }

        if (!$this->fs->exists($this->path->toSeederStub())) {
            throw new FileNotFoundException('The seeder stub does not exist');
        }

        $content = $this->fs->get($this->path->toSeederStub());

        if (!$this->fs->put($this->path->toProjectSeederFile(), $content)) {
            throw new RuntimeException('The seeder file could not be created');
        }
    }

    /**
     * @return void
     */
    public function deleteGenericSeeders()
    {
        $seederClasses = $this
            ->seedResources()
            ->map(function (string $s) {
                return str($s)
                    ->trim("'")
                    ->append('Seeder.php')
                    ->toString();
            })
            ->all();

        $finder = (new Finder())
            ->files()
            ->in($this->path->toSeederDirectory())
            ->name($seederClasses);

        foreach($finder as $genericSeederFile) {
            if (false !== $path = $genericSeederFile->getRealPath()) {
                $this->fs->delete($path);
            }
        }
    }

    /**
     * @return Collection<string>
     */
    private function seedResources()
    {
        return str($this->draft->attribute('seeders'))
            ->explode(',')
            ->map(fn (string $s) => (string) str($s)->trim()->wrap("'"));
    }

    /**
     * @return string|null
     */
    public function scanRequiredMigrations()
    {
        $migrationsDir = join_paths($this->path->toProject(), 'database', 'migrations');

        [$hasUserMigration, $hasTeamMigration] = [false, !$this->isUsingTeams];
        foreach(scandir($migrationsDir) ?: [] as $fileName) {
            if (str($fileName)->is('*create_users_table.php')) {
                $hasUserMigration = true;
            }

            if (str($fileName)->is('*create_teams_table.php')) {
                $hasTeamMigration = true;
            }
        }

        if ($hasUserMigration && $hasTeamMigration) {
            return null;
        }

        $missingMigrations = collect(['user' => !$hasUserMigration, 'team' => !$hasTeamMigration])
            ->filter()
            ->keys();

        return sprintf("%s seeder bypassed: the %s %s %s missing",
            $this->draft->template(),
            $missingMigrations->join(' and '),
            Pluralizer::plural('migration', $missingMigrations),
            $missingMigrations->count() > 1 ? 'are' : 'is'
        );
    }
}
