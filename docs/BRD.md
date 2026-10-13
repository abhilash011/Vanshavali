# Business Requirements Document (BRD)

## 1. Document Purpose

This document defines the business requirements for a **Hindu Community & Family Tree Portal**. The portal will be a secure, inclusive digital platform for Hindu communities, families, temples, associations, and community administrators to connect, preserve family heritage, share trusted information, and manage community participation.

The system expands the current Sonar Samaj-focused portal into a configurable platform that can serve all Hindu communities without favouring or excluding any caste, language, region, tradition, or denomination.

## 2. Business Need

Hindu families and community organisations often maintain family relationships, member details, announcements, event information, and heritage records in paper registers, spreadsheets, messaging groups, or separate local systems. These methods make information difficult to maintain, search, verify, and pass to future generations.

The portal will provide one central, accessible, privacy-conscious system for:

- Maintaining family trees and member records.
- Enabling approved members to access community services.
- Publishing verified community, temple, cultural, educational, and social-welfare information.
- Supporting multiple Hindu communities under appropriate local administration.

## 3. Vision

To create a trusted digital home for Hindu communities worldwide that strengthens family connections, preserves cultural heritage, and supports community service while respecting diversity, privacy, and individual dignity.

## 4. Objectives

- Provide a responsive public website and authenticated member portal.
- Allow authorised users to create, view, and maintain family-tree relationships.
- Support multiple communities, regions, languages, and traditions through configurable content and administration.
- Improve communication of events, notices, services, and opportunities.
- Preserve historical and family information for future generations.
- Protect personal information through secure access, consent, validation, and role-based permissions.

## 5. Scope

### In Scope

- Public community portal with information pages, galleries, testimonials, notices, and event details.
- Member registration, sign-in, password/session management, and profile management.
- Family-tree creation, visualisation, search, and authorised editing.
- Member records, household/family relationships, profile photographs, and supporting documents where approved.
- Community, temple, association, regional, and chapter administration.
- Events, announcements, volunteering, education, matrimonial/community-service notices where enabled by each community.
- Role-based approval and moderation workflows.
- Multi-language-ready interface, initially supporting English and extensible to Indian languages.
- Administrative reporting, audit history, and data export subject to permissions.

### Out of Scope for Initial Release

- Payment collection, donation processing, or financial accounting.
- Government identity verification or legal genealogy certification.
- Automated matching, profiling, or decision-making based on caste, religion, gender, age, disability, or other sensitive personal attributes.
- Public exposure of private member, relationship, or contact details without explicit permission.

## 6. Stakeholders

| Stakeholder | Responsibilities / Interests |
|---|---|
| Community members | Maintain profiles, connect with family, access community information. |
| Family coordinators | Propose and maintain information for their own family branch. |
| Community administrators | Approve members, manage content, resolve data issues, and oversee privacy. |
| Temple / association representatives | Publish verified local notices, events, and services. |
| Platform administrators | Configure communities, access controls, security, backups, and support. |
| Visitors | View approved public information and request membership. |

## 7. User Roles

- **Visitor:** Can view public pages and submit a membership request.
- **Member:** Can manage their own profile, view permitted family/community information, and submit corrections.
- **Family Coordinator:** Can propose or update authorised records for an assigned family branch.
- **Content Moderator:** Can review announcements, photos, and public content.
- **Community Administrator:** Can approve members, manage local community data, events, and permissions.
- **Platform Administrator:** Has controlled system-level access across communities for configuration, support, security, and compliance.

## 8. Functional Requirements

### 8.1 Public Portal

- The system shall provide a mobile-responsive landing page for each participating community or chapter.
- The system shall publish approved information about the community, history, values, services, events, galleries, testimonials, and contact details.
- The system shall allow visitors to locate a community or chapter by name and region.
- The system shall display only content approved for public visibility.

### 8.2 Registration and Authentication

- The system shall allow eligible users to submit a registration request.
- The system shall validate required data and prevent duplicate accounts where feasible.
- The system shall support administrator approval before granting member access when configured by a community.
- The system shall provide secure login, logout, session expiry, password reset, and account recovery capabilities.
- The system shall maintain a record of approval, rejection, suspension, and major account changes.

### 8.3 Member Profiles

- Members shall be able to view and update their own permitted profile details.
- Profiles may include name, preferred name, contact details, location, family links, education, occupation, interests, profile photograph, and optional cultural information.
- Every sensitive or optional field shall have a defined visibility setting where practical, such as private, family-only, community-only, or public.
- Members shall be able to request correction or removal of inaccurate information.

### 8.4 Family Tree Management

- The system shall store family-member records and relationships such as parent, child, spouse, sibling, and guardian where applicable.
- The system shall provide a visual family-tree view and a searchable member directory.
- Authorised users shall be able to add or propose a family member, edit a record, link relationships, and upload a profile image.
- Changes to shared family data shall require approval, or be fully logged and reversible, based on community policy.
- The system shall handle incomplete, unknown, deceased, adopted, blended, and non-traditional family relationships respectfully and without forcing disclosure.
- The system shall not make private tree information publicly searchable by default.

### 8.5 Community Content and Events

- Administrators and moderators shall be able to create, edit, schedule, publish, and archive announcements and events.
- Events may include religious observances, temple programmes, cultural activities, education, health camps, charitable activities, meetings, and celebrations.
- The system shall support event details, location, date/time, contact person, registration link or RSVP status, and visibility controls.
- The system shall allow approved image galleries and community stories.

### 8.6 Administration and Reporting

- Administrators shall have a dashboard for member approvals, pending edits, content moderation, events, and basic activity summaries.
- The system shall support separate administration boundaries for each community/chapter while retaining platform-level oversight.
- Administrators shall be able to export only the data for which they are authorised.
- The system shall create an audit trail for authentication, approvals, family-tree changes, administrator actions, and data exports.

## 9. Non-Functional Requirements

- **Usability:** The interface must be easy to use on desktop and mobile devices, including for users with limited technical experience.
- **Accessibility:** Core journeys should meet WCAG 2.1 AA principles, including keyboard access, readable contrast, form labels, and image alt text.
- **Performance:** Common pages should load promptly on typical mobile connections; family-tree views should remain usable for large families.
- **Security:** Use HTTPS, secure passwords, server-side validation, CSRF protection, safe file uploads, least-privilege access, and secure session handling.
- **Privacy:** Follow applicable privacy laws and obtain consent before publishing personal information or photographs.
- **Reliability:** Back up member and tree data regularly; provide a documented restore process.
- **Scalability:** The design must support growth from one local group to many communities and chapters.
- **Maintainability:** Configuration, credentials, and environment-specific settings must be kept outside source control.

## 10. Privacy, Inclusion, and Governance Requirements

- Participation must be voluntary, and members must be able to control eligible profile visibility.
- The portal must welcome all Hindu communities and traditions equally, including communities differentiated by region, language, temple tradition, or social organisation.
- No user shall be excluded, ranked, or treated unfairly based on caste, sub-caste, gender, marital status, disability, age, language, region, or economic background.
- Caste or similar sensitive information must never be mandatory. If a participating community elects to collect it for a legitimate, lawful local purpose, it must be optional, access-controlled, consent-based, and not used for discriminatory matching or public display.
- Information about minors must have enhanced privacy controls and must not be publicly visible by default.
- Administrators must have a process to respond to privacy requests, corrections, disputes, and inappropriate-content reports.
- Community policies, terms of use, and a privacy notice must be available before registration.

## 11. Data Requirements

Core data entities include:

| Entity | Key Information |
|---|---|
| Community / Chapter | Name, region, language preferences, contact details, branding, policies. |
| User Account | Login identity, authentication data, role, status, audit information. |
| Member Profile | Personal details, visibility preferences, photograph, optional profile attributes. |
| Family Relationship | Linked members, relationship type, verification status, effective dates where needed. |
| Event / Announcement | Title, content, audience, schedule, publisher, approval status. |
| Media Asset | File, owner, usage consent, visibility, moderation status. |
| Audit Record | Actor, action, target, date/time, outcome. |

## 12. Business Rules

- A user may access only information permitted by their role and the relevant visibility setting.
- New registrations and shared family-tree changes must be approved or auditable according to the community’s configured governance policy.
- A profile image must be accepted only after file type, size, and security validation.
- A member may request removal or correction of their own data; administrators must process the request under the privacy policy.
- Each community/chapter may customise public content and local workflow rules, but cannot weaken platform-level security, privacy, inclusion, or audit requirements.
- Data export must be restricted, logged, and limited to the authorised community scope.

## 13. Success Measures

- Number of approved and active members.
- Percentage of members with complete, consented profiles.
- Number of verified family relationships and family branches maintained.
- Event notice reach, RSVP participation, and community engagement.
- Registration and content-approval turnaround time.
- Number and resolution time of privacy, correction, and support requests.
- Zero confirmed unauthorised public disclosures of private member data.

## 14. Initial Implementation Considerations

The current PHP, Bootstrap, MySQL, JSON, and family-tree structure provides a foundation for the first release. Before a broad community rollout, the project should prioritise:

1. Moving secrets from PHP source files into secure environment configuration.
2. Replacing direct JSON writes with a transactional database-backed member and relationship model.
3. Adding robust role-based access control, moderation, audit logging, and safe image upload handling.
4. Implementing privacy controls, consent capture, account recovery, and data-retention processes.
5. Testing authentication, authorisation, family-tree editing, and backup/restore workflows.

## 15. Assumptions and Dependencies

- Each participating community will appoint accountable administrators and moderators.
- Communities will supply approved public content, policies, and local contact details.
- Users will have internet access and an email address or other supported verified contact method.
- Production deployment will provide HTTPS, secure hosting, database backups, and operational support.
- Legal/privacy obligations will be reviewed for the regions in which the portal is operated.

## 16. Approval

This BRD should be reviewed and approved by platform owners, community representatives, privacy/security stakeholders, and technical delivery leads before production development or a multi-community launch.
