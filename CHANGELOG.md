<h1>UniCore ERP - CHANGELOG</h1>

<p>This document tracks all notable changes and the current status of the UniCore ERP Admission Lifecycle Management Module.</p>

<hr>

<h2>Release v2.0-beta (2026-01-14)</h2>

<p><strong>STATUS: EXPERIMENTAL BUILD</strong></p> <p>This version is currently undergoing active testing and is <b>not considered a stable release</b>. It is intended for demonstration and internal auditing purposes only.</p>

<h3>New Features</h3> <ul> <li><strong>Dynamic RBAC (V2.0):</strong> Implementation of a granular permission matrix mapped via the <i>role_permissions</i> table.</li> <li><strong>Single-Group Lead Tracking:</strong> Automated grouping of multi-course applications via phone-number anchoring to prevent duplicate student profiles.</li> <li><strong>Verification Terminal:</strong> A high-fidelity "Compliance Desk" featuring live PDF previews for marksheets and integrated engagement logging.</li> <li><strong>Automated Provisioning:</strong> Added <i>utility/setup_demo.php</i> for instant environment setup, directory creation, and database seeding.</li> </ul>

<h3>Technical Fixes</h3> <ul> <li><strong>Token Management:</strong> Resolved "Duplicate Token" errors by allowing <i>access_token</i> to accept NULL values in the database schema.</li> <li><strong>Security Logic:</strong> Fixed a Fatal Error in the <i>has_perm()</i> function by standardizing the include order of <i>Security.php</i> in page headers.</li> <li><strong>UI Alignment:</strong> Standardized terminal layouts to ensure the sidebar and main work areas are flush without gaps.</li> </ul>

<h3>Known Issues (Untested & In-Progress)</h3> <p>The following items are recognized as unstable or incomplete in this beta phase:</p>

<ul> <li><strong>API Integration:</strong> The <b>Social Router</b> and <b>WhatsApp Webhook</b> receivers are currently untested against live production endpoints; logic is based on internal simulations.</li> <li><strong>Session Management:</strong> Administrators must manually logout and login to refresh permissions after making changes in the <b>Access Matrix</b>.</li> <li><strong>Account Recovery:</strong> There is no "Forgot Password" or email-based recovery flow; password resets require direct database intervention.</li> <li><strong>Data Conflicts:</strong> A formalized "Unmerge" tool for leads accidentally grouped by shared family phone numbers is currently missing.</li> <li><strong>Responsiveness:</strong> The Verification Terminal is desktop-only and may experience UI overlap on screen widths below 1440px.</li> </ul>

<h3>Planned for v2.1-stable</h3> <ul> <li>Full integration with the <i>college_requirements</i> schema to drive dynamic document checklists.</li> <li>Implementation of a session-refresh API to update staff permissions instantly.</li> <li>End-to-end validation with the WhatsApp Business API sandbox.</li> </ul>

<hr> <p>UniCore Admission Module • Beta Documentation 2026</p>
