<h1>UniCore ERP V2.0 - Admission Lifecycle Management Module</h1>

<p>UniCore ERP V2.0 is a modular system built to manage the journey from inquiry to enrollment. It uses a Dynamic RBAC system and a centralized Verification Terminal to ensure institutional accountability.</p>

<h2>Section 1: Technical Overview</h2>

<h3>Key Features</h3> <ul> <li><strong>Dynamic RBAC (V2.0):</strong> Maps system permissions like <i>view_compliance</i> to user roles.</li> <li><strong>Single-Group Tracking:</strong> Groups multiple applications from one student using their phone number as a unique anchor.</li> <li><strong>Verification Terminal:</strong> A Compliance Desk for auditing student documents and logging real-time calls.</li> <li><strong>Social Lead Router:</strong> Tracks leads from WhatsApp and external APIs to prevent data loss.</li> </ul>

<h3>Project Architecture</h3> <p>The system is organized into specific directories to separate core logic from the user interface.</p>

<ul> <li><strong>/api:</strong> Handlers for status updates, document verification, and permission toggles.</li> <li><strong>/core & /services:</strong> Contains the Security.php gatekeeper and LeadService.php business logic.</li> <li><strong>/config:</strong> Database connection and environment settings.</li> <li><strong>/includes:</strong> Sidebar, header, and reusable table components.</li> <li><strong>/uploads/docs:</strong> Storage for student PDFs and identity files.</li> <li><strong>/utility:</strong> Includes setup_demo.php and help manuals.</li> </ul>

<hr>

<h2>Section 2: Operations Manual</h2>

<h3>Chapter 1: Initial System Provisioning</h3> <p>Before processing leads, the environment must be initialized to establish the database and folder structures.</p> <ol> <li>Navigate to <b>utility/setup_demo.php</b> in your browser to create the <i>unicore_db</i> and <i>/uploads/docs/</i> directory.</li> <li>Login at <b>login.php</b> using the Superadmin credentials: username <b>admin</b> and password <b>admin123</b>.</li> <li>The script also seeds a pre-seeded "John Doe" lead to test multi-course grouping.</li> </ol>

<h3>Chapter 2: Lead Ingestion and Single-Group Tracking</h3> <p>The module handles multiple applications for the same student through automated grouping.</p> <ul> <li><strong>Data Anchoring:</strong> Leads are grouped based on a shared phone number to prevent redundant profiles.</li> <li><strong>Source Identification:</strong> The <b>Social Router</b> identifies if a lead arrived via WhatsApp, API, or Manual entry.</li> <li><strong>Constraint Management:</strong> By allowing <i>access_token</i> to be NULL, the system prevents duplicate entry errors during multi-course registration.</li> </ul>

<h3>Chapter 3: The Verification Terminal (Compliance Desk)</h3> <p>Counselors use this desk to move students through the "Compliance Gate".</p> <ol> <li><strong>Document Audit:</strong> Upload or attach the four required academic documents.</li> <li><strong>Live Preview:</strong> Click the "View" button to inspect PDF marksheets directly in the sidebar viewer.</li> <li><strong>Engagement Logs:</strong> Use the integrated call timer and WhatsApp tool to record every interaction.</li> </ol>

<h3>Chapter 4: Financial Gate and Final Enrollment</h3> <p>Once compliance is met, the lead moves to the final fee audit stage.</p> <ul> <li><strong>Fee Verification:</strong> Audit the student's <i>payment_ref</i> against financial records.</li> <li><strong>Finalization:</strong> Click "Verify & Enroll" to move the lead to the Enrolled Archive.</li> <li><strong>Automation:</strong> Successful enrollment triggers an automated WhatsApp receipt dispatch.</li> </ul>

<h3>Chapter 5: Role-Based Access Governance</h3> <p>Administrators manage staff visibility through the <b>Access Matrix</b>.</p> <ul> <li><strong>Permission Keys:</strong> Toggle keys like <i>view_finance</i> or <i>social_router</i> to instantly update sidebar visibility.</li> <li><strong>Security Logic:</strong> The <i>Security.php</i> core file ensures that users without specific keys cannot access restricted modules.</li> </ul>

<hr> <p><b>UniCore ERP - Admission Lifecycle Management Module • 2026</b></p>
