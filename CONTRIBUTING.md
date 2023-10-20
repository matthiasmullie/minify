# How to contribute


## Issues

When [filing bugs](https://github.com/matthiasmullie/minify/issues/new),
try to be as thorough as possible:
* What version did you use?
* What did you try to do? ***Please post the relevant parts of your code.***
* What went wrong? ***Please include error messages, if any.***
* What was the expected result?


## Pull requests

Bug fixes and general improvements to the existing codebase are always welcome.
New features are also welcome, but will be judged on an individual basis. If
you'd rather not risk wasting your time implementing a new feature only to see
it turned down, please start the discussion by
[opening an issue](https://github.com/matthiasmullie/minify/issues/new).


### Testing

Please include tests for every change or addition to the code.
To run the complete test suite:

```sh
make test
```

GitHub Actions have been [configured](.github/workflows/test.yml) to run supported
PHP versions. Upon submitting a new pull request, that test suite will be run &
report back on your pull request. Please make sure the test suite passes.


### Coding standards

All code must follow [PSR-12](http://www.php-fig.org/psr/psr-12/). Just make sure
to run php-cs-fixer before submitting the code, it'll take care of the
formatting for you:

```sh
make format
```

Document the code thoroughly!


## License

Note that minify is MIT-licensed, which basically allows anyone to do
anything they like with it, without restriction.
