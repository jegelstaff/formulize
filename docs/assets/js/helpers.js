/**
 * Update an existing GitHub download link with the latest release information
 * @param {string} owner - Repository owner/organization
 * @param {string} repo - Repository name
 * @param {string} linkSelector - CSS selector for the link to update
 */
function updateGitHubReleaseLink(owner, repo, linkSelector = '.downloads-icon-link') {
  // Get the link element
  const linkElement = document.querySelector(linkSelector);

  // If the link doesn't exist, exit early
  if (!linkElement) {
    console.error(`Link element with selector "${linkSelector}" not found`);
    return;
  }
  // Find the text element that should contain the version number
  const textElement = linkElement.querySelector('.downloads-icon-link-text');
  if (textElement) {
    // Store original text as fallback
    const originalText = textElement.innerHTML;
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

      // Update the link's href to point to the specific version zip
      linkElement.href = `https://github.com/${owner}/${repo}/zipball/${tagName}`;

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
