/**
 * Update an existing GitHub download link with the latest release information
 * @param {string} owner - Repository owner/organization
 * @param {string} repo - Repository name
 * @param {string} linkSelector - CSS selector for the link to update
 */
function updateGitHubReleaseLink(owner, repo, linkSelectors = ['.downloads-icon-link', '.download-latest-release-link']) {
  // Get the icon link element
  const iconLinkElement = document.querySelector(linkSelectors[0]);
	// Get the latest release link
	const latestReleaseLinkElement = document.querySelector(linkSelectors[1])
	// Find the text element that should contain the version number
  const textElement = iconLinkElement.querySelector('.downloads-icon-link-text');

  // If the link doesn't exist, exit early
  if (!iconLinkElement) {
    console.error(`Icon Link Element could not be found`);
    return;
  }

  // Fetch the latest release data from GitHub API
  fetch(`https://api.github.com/repos/${owner}/${repo}/releases/latest`)
    .then(response => {
      if (!response.ok) {
        throw new Error(`GitHub API error: ${response.status}`);
      }
      return response.json();
    })
    .then(data => {
      // Extract the tag name
      const tagName = data.tag_name;

      // Update the link hrefs to point to the specific version zip
      iconLinkElement.href = `https://github.com/${owner}/${repo}/zipball/${tagName}`;
			latestReleaseLinkElement.href = `https://github.com/${owner}/${repo}/zipball/${tagName}`;

      // Update the text content if the text element exists
      if (textElement) {
        textElement.innerHTML = `Download <strong>${tagName}</strong>`;
      }

    })
    .catch(error => {
      console.error('Error fetching GitHub release:', error);
      // Link already set to fallback URL, so no further action needed
    });
}
