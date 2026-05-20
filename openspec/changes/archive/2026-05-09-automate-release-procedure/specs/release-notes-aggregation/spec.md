## ADDED Requirements

### Requirement: Per-repo notes generated via GitHub generate-notes API

For every release-eligible repo where the chosen version differs from
`from_pin[repo]`, the CLI SHALL request release notes from GitHub's
generate-notes endpoint:
`POST /repos/<owner>/<repo>/releases/generate-notes` with
`tag_name=<chosen>` and `previous_tag_name=<from_pin[repo]>`. The CLI
SHALL NOT parse commit messages, derive categories, or produce note
text itself.

#### Scenario: Per-repo API call

- **WHEN** the CLI computes that `shop-ce` ships at `v1.6.2` over
  `from_pin[shop-ce] = v1.6.1`
- **THEN** the CLI calls
  `POST /repos/o3-shop/shop-ce/releases/generate-notes` with
  `tag_name=v1.6.2` and `previous_tag_name=v1.6.1` and uses the
  returned markdown body verbatim for that repo's section

### Requirement: Per-repo notes use that repo's release.yml configuration

The CLI SHALL rely on each release-eligible repo's existing
`.github/release.yml` (if any) for category structure, label
filters, and contributor formatting. The CLI SHALL NOT inject or
override per-repo release-yml configuration.

#### Scenario: Repo has custom release.yml

- **WHEN** `gdpr-optin-module/.github/release.yml` defines
  categories `Features` / `Fixes` / `Internal`
- **THEN** the markdown returned by GitHub for that repo (and used
  by the CLI in the aggregated body) reflects those categories

### Requirement: Aggregated body has one H2 per changed repo

The aggregated cross-repo release notes SHALL include one second-level
markdown heading (`## <repo>`) per release-eligible repo whose chosen
version differs from `from_pin[repo]`. Under each heading SHALL
appear the markdown body returned by GitHub's generate-notes API for
that repo.

#### Scenario: Two repos changed, three unchanged

- **WHEN** `shop-ce` and `testing-library` both have `chosen !=
  from_pin`, while `smarty`, `wave-theme`, and `shop-facts` have
  `chosen == from_pin`
- **THEN** the aggregated body has exactly one `## shop-ce` heading
  and one `## testing-library` heading, each followed by the GitHub
  generate-notes markdown for that repo

### Requirement: Unchanged-in-this-release section

The aggregated body SHALL include a section
`## Unchanged in this release` listing every release-eligible repo
where `chosen == from_pin[repo]`, with the version each repo
continues to ship at.

#### Scenario: Three repos unchanged

- **WHEN** `smarty` (`v1.0.3`), `wave-theme` (`v1.1.0`), and
  `shop-facts` (`v1.0.4`) are all unchanged from `from_pin`
- **THEN** the aggregated body contains a section
  `## Unchanged in this release` listing each of those repos with
  its continued version

### Requirement: Aggregated body attached to o3-shop draft release

The aggregated markdown SHALL be set as the body of the o3-shop
draft GitHub release created for `--to`. Per-repo draft releases
created at each repo's tag SHALL retain their own GitHub-generated
notes; the aggregation is additive, not a replacement.

#### Scenario: o3-shop draft release body

- **WHEN** the CLI creates the o3-shop draft release for `--to v1.6.2`
- **THEN** the release body contains the aggregated markdown
  (one section per changed repo plus the Unchanged section)

#### Scenario: Per-repo notes still exist

- **WHEN** the CLI creates a draft release on `shop-ce` for
  `v1.6.2`
- **THEN** that release has its own GitHub-generated body produced
  by the same generate-notes API for the shop-ce repo, independent
  of the o3-shop aggregated body
