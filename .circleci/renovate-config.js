const circleciRepoSlug =
	process.env.CIRCLE_PROJECT_USERNAME && process.env.CIRCLE_PROJECT_REPONAME
		? `${process.env.CIRCLE_PROJECT_USERNAME}/${process.env.CIRCLE_PROJECT_REPONAME}`
		: undefined;

module.exports = {
	$schema: 'https://docs.renovatebot.com/renovate-schema.json',
	platform: 'github',
	gitAuthor:
		process.env.RENOVATE_GIT_AUTHOR ||
		'Renovate Bot <renovate-bot@users.noreply.github.com>',
	repositories: circleciRepoSlug ? [circleciRepoSlug] : [],

	extends: ['config:recommended'],

	timezone: 'Europe/Paris',
	labels: ['dependencies'],
	dependencyDashboard: true,
	prConcurrentLimit: 10,
	rebaseWhen: 'behind-base-branch',

	ignorePaths: ['vendor/**', 'var/**', 'node_modules/**'],

	packageRules: [
		{
			matchManagers: ['composer'],
			groupName: 'composer dependencies',
			matchUpdateTypes: ['minor', 'patch'],
		},
		{
			matchManagers: ['npm'],
			groupName: 'npm dependencies',
			matchUpdateTypes: ['minor', 'patch'],
		},
		{
			matchUpdateTypes: ['major'],
			labels: ['dependencies', 'major-update'],
		},
	],
};
