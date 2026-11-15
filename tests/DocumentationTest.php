<?php

use Nyamort\LaravelAnonymizer\Contracts\AnonymizationStrategy;
use Nyamort\LaravelAnonymizer\Tests\Fixtures\User;
use Symfony\Component\Process\Process;

it('compiles the PHP examples and checks their strategy contracts', function () {
    preg_match_all('/[~\x60]{3}php\\R(.*?)[~\x60]{3}/s', file_get_contents(__DIR__.'/../README.md'), $matches);

    expect($matches[1])->not->toBeEmpty();

    foreach ($matches[1] as $example) {
        $filename = tempnam(sys_get_temp_dir(), 'anonymizer-readme-');

        try {
            file_put_contents($filename, "<?php\n".$example);
            $process = new Process([PHP_BINARY, '-l', $filename]);
            $process->run();
            expect($process->isSuccessful())->toBeTrue($process->getOutput().$process->getErrorOutput());

            if (str_contains($example, 'class Documentation')) {
                eval($example);
            }
        } finally {
            unlink($filename);
        }
    }

    $user = DocumentationUser::create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'note' => 'secret',
        'meta' => ['city' => 'Paris'],
        'phone' => '0612345678',
    ]);

    expect($user->delete())->toBeTrue();
    expect(User::withTrashed()->findOrFail($user->id)->name)->toBe('[anonymous]');
    expect(new DocumentationUppercase)->toBeInstanceOf(AnonymizationStrategy::class);
    expect(new DocumentationFakeEmail)->toBeInstanceOf(AnonymizationStrategy::class);
});
