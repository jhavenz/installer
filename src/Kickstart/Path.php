<?php

declare(strict_types=1);

namespace Laravel\Installer\Console\Kickstart;

use function Illuminate\Filesystem\join_paths;

class Path
{
    /**
     * @var Draft
     */
    protected $draft;

    /**
     * @var string
     */
    protected $projectPath;

    public function __construct(string $projectPath)
    {
        $this->projectPath = $projectPath;
    }

    /**
     * @return string
     */
    public function toProject()
    {
        return $this->projectPath;
    }

    /**
     * @return string
     */
    public function toSeederDirectory()
    {
        return join_paths(dirname(__DIR__, 2), 'stubs', 'kickstart', $this->draft->template());
    }

    /**
     * @return string
     */
    public function toProjectSeederFile()
    {
        return match($this->draft->template()) {
            'blog' => join_paths($this->toProject(), 'database', 'seeders', 'BlogKickstartSeeder.php'),
            'podcast' => join_paths($this->toProject(), 'database', 'seeders', 'PodcastKickstartSeeder.php'),
            'phone-book' => join_paths($this->toProject(), 'database', 'seeders', 'PhoneBookKickstartSeeder.php'),
        };
    }

    /**
     * @return string
     */
    public function toSeederStub()
    {
        return join_paths(
            $this->toSeederDirectory(),
            basename($this->toProjectSeederFile()).'.stub',
        );
    }

    /**
     * @param  Draft  $draftFile
     * @return void
     */
    public function setDraft(Draft $draftFile)
    {
        $this->draft = $draftFile;
    }
}
