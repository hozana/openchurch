'use strict';

const https = require('https');

const owner = process.env.GITHUB_OWNER || 'hozana';
const repo = process.env.GITHUB_REPO || 'backend';

const token = process.env.RENOVATE_TOKEN || process.env.GITHUB_COM_TOKEN;
if (!token) {
	console.error('RENOVATE_TOKEN/GITHUB_COM_TOKEN is missing in CircleCI context');
	process.exit(1);
}

const tokenSource = process.env.RENOVATE_TOKEN ? 'RENOVATE_TOKEN' : 'GITHUB_COM_TOKEN';

const requestJson = (options, body) =>
	new Promise((resolve, reject) => {
		const req = https.request(options, (res) => {
			let data = '';
			res.on('data', (chunk) => {
				data += chunk;
			});
			res.on('end', () => {
				let parsed = data;
				try {
					parsed = JSON.parse(data);
				} catch (_e) {
					// Keep raw body for debugging.
				}
				resolve({ statusCode: res.statusCode || 0, body: parsed });
			});
		});
		req.on('error', reject);
		if (body) {
			req.write(JSON.stringify(body));
		}
		req.end();
	});

const commonHeaders = {
	Authorization: `Bearer ${token}`,
	'User-Agent': 'circleci-renovate-preflight',
	Accept: 'application/vnd.github+json',
	'X-GitHub-Api-Version': '2022-11-28',
};

(async () => {
	console.log(`Using token from ${tokenSource}`);

	const me = await requestJson({
		hostname: 'api.github.com',
		path: '/user',
		method: 'GET',
		headers: commonHeaders,
	});

	if (me.statusCode !== 200 || !me.body?.login) {
		console.error(`Auth preflight failed with status ${me.statusCode}`);
		console.error(me.body);
		process.exit(1);
	}

	console.log(`Authenticated as ${me.body.login}`);

	const rest = await requestJson({
		hostname: 'api.github.com',
		path: `/repos/${owner}/${repo}`,
		method: 'GET',
		headers: commonHeaders,
	});

	if (rest.statusCode !== 200) {
		console.error(`REST preflight failed with status ${rest.statusCode}`);
		console.error(rest.body);
		if (rest.statusCode === 404) {
			console.error('Private repository is not visible to this token/user.');
			console.error(`If SSO is not available, use a fine-grained PAT owned by org "${owner}" with access to ${repo}.`);
			console.error('Alternative: use a bot/service account that is member of the organization and has repo access.');
		}
		process.exit(1);
	}

	const graphql = await requestJson(
		{
			hostname: 'api.github.com',
			path: '/graphql',
			method: 'POST',
			headers: {
				...commonHeaders,
				'Content-Type': 'application/json',
			},
		},
		{
			query: `query { repository(owner: "${owner}", name: "${repo}") { id nameWithOwner viewerPermission } }`,
		},
	);

	const hasGraphqlErrors = graphql.body && graphql.body.errors && graphql.body.errors.length > 0;
	if (graphql.statusCode !== 200 || hasGraphqlErrors || !graphql.body?.data?.repository) {
		console.error(`GraphQL preflight failed with status ${graphql.statusCode}`);
		console.error(graphql.body);
		process.exit(1);
	}

	console.log(`GitHub preflight passed for ${owner}/${repo}`);
})().catch((error) => {
	console.error('Preflight request failed');
	console.error(error && error.message ? error.message : error);
	process.exit(1);
});
