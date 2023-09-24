# Contributing to Authorship

Code contributions, bug reports, and feedback are very welcome. These should be submitted through [the GitHub repository](https://github.com/humanmade/authorship). Development happens in the `develop` branch, and any pull requests should be made against that branch please.

* [Setting up Locally](#setting-up-locally)
* [Building the Assets](#building-the-assets)
* [Running the Tests](#running-the-tests)
* [Releasing a New Version](#releasing-a-new-version)


## Setting up Locally

You can clone this repo and activate it like a normal WordPress plugin, but you'll need to install the developer dependencies in order to build the assets and to run the tests.

### Prerequisites

* [Composer](https://getcomposer.org/)
* [Node](https://nodejs.org/)

### Setup

1. Install the PHP dependencies:

       composer install

2. Install the Node dependencies:

       npm install

3. If you want to run the tests locally, check the MySQL database credentials in the `tests/.env` file and amend them as necessary.

## Building the Assets

To compile the JS and CSS:

	npm run build

To start the file watcher which will watch for changes and automatically build the JS and CSS:

	npm run start

## Running the Tests

To run the whole test suite which includes unit tests and linting:

	composer test

To run just the PHPUnit tests:

	composer test:ut

To run just the code sniffer:

	composer test:phpcs

To run just the PHP Static Analysis tool:

	composer test:phpstan

To lint the JS and CSS:

	npm run lint

## Releasing a New Version

These are the steps to take to release a new version of Authorship (for contributors who have push access to the GitHub repo).

### Prior to Release

1. Check [the milestone on GitHub](https://github.com/humanmade/authorship/milestones) for open issues or PRs. Fix or reassign as necessary.
1. If this is a non-patch release, check issues and PRs assigned to the patch or minor milestones that will get skipped. Reassign as necessary.
1. Ensure you're on the `develop` branch and all the changes for this release have been merged in.
1. Ensure `README.md` contains an up to date description, "Tested up to" version, FAQs, screenshots, etc.
    * **Note:** The double spaces at the end of the lines in the plugin header section at the top of README.md must be kept, to ensure line breaks are created.
3. Run `composer test` and ensure everything passes.
4. Prepare a changelog for [the Releases page on GitHub](https://github.com/humanmade/authorship/releases).
   - The `git changelog -x` command from [Git Extras](https://github.com/tj/git-extras) is handy for this.

### For Release

1. Bump and commit the plugin version number using `npm run bump:{INCEMENTOR}`, eg: `npm run bump:patch`, `npm run bump:minor`, or `npm run bump:major`
1. Push to a new release branch, eg: `git checkout -b release-vxxx` and then `git push`
1. Create a new pull-request from that branch, eg: `hub pull-request`
1. Wait until (and ensure that) [the build passes](https://github.com/humanmade/authorship/actions)
1. Get the PR reviewed and merged
1. `git checkout main`
1. `git merge develop`
1. `git push origin main`
1. `git push origin main:release`
1. Wait for [the Build Release action](https://github.com/humanmade/authorship/actions?query=workflow%3A%22Build+Release%22) to complete
1. Enter the changelog into [the release on GitHub](https://github.com/humanmade/authorship/releases) and publish it.
