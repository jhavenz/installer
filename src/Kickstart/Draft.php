<?php

declare(strict_types=1);

namespace Laravel\Installer\Console\Kickstart;

use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;

use function Illuminate\Filesystem\join_paths;

/**
 * @phpstan-type DraftAttributes = array{
 *     kickstart-template: string,
 *     models: array<string, array<string, string>>,
 *     controllers: array<string, array<string, string>>,
 *     seeders: string[]
 * }
 */
class Draft
{
    /**
     * @var Path
     */
    protected $path;

    /**
     * @var 'blog' | 'podcast' | 'phone-book'
     */
    protected $template;

    /**
     * @var DraftAttributes
     */
    protected $yaml;

    public function __construct(Path $paths, string $template)
    {
        throw_unless(
            in_array($template, ['blog', 'podcast', 'phone-book']),
            new InvalidArgumentException("The {$template} kickstart template does not exist")
        );

        $this->path = $paths;
        $this->template = $template;
        $this->path->setDraft($this);
    }

    /**
     * @param  string|null  $attribute
     * @param  mixed|null  $default
     * @return ($attribute is null ? DraftAttributes : string[] | string)
     */
    public function attribute(?string $attribute = null, mixed $default = null)
    {
        return data_get($this->yaml(), $attribute, $default);
    }

    /**
     * @param  bool  $teams
     * @return void
     */
    public function create(bool $teams)
    {
        $from = $this->stub($teams);

        $fs = new Filesystem();

        $fs->copy($from, $this->filePath());
    }

    /**
     * @return bool
     */
    public function existsInProject()
    {
        return file_exists($this->filePath());
    }

    /**
     * @return string
     */
    public function filePath()
    {
        return join_paths($this->path->toProject(), 'draft.yaml');
    }

    /**
     * @return string
     */
    public function stub(bool $teams = false)
    {
        $fileName = sprintf(
            '%s/draft%s.yaml',
            $this->template,
            $teams ? 'with-teams' : ''
        );

        return join_paths(dirname(__DIR__, 2), 'stubs', 'kickstart', $fileName);
    }

    /**
     * @return string
     */
    public function template()
    {
        return $this->template;
    }

    /**
     * @return array
     */
    public function yaml()
    {
        if (!isset($this->yaml)) {
            $this->yaml = Yaml::parseFile($this->filePath());
        }

        return $this->yaml;
    }
}
