<?php
require_once 'db_connection.php';
require_once 'session_start.php';

// Ensure session is started and check login status immediately
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Get user ID from session
$userId = $_SESSION['user_id'];

// Load branding configuration from database
try {
    $stmt = $conn->query("SELECT * FROM branding LIMIT 1");
    $brandingData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Transform database data into the expected structure
    $brandingConfig = [
        'companyInfo' => [
            'name' => $brandingData['company_name'] ?? 'Company Name',
            'tagline' => $brandingData['tagline'] ?? '',
            'foundedYear' => $brandingData['founded_year'] ?? '',
            'headquartersLocation' => $brandingData['headquarters_location'] ?? '',
            'websiteUrl' => $brandingData['website_url'] ?? ''
        ],
        'visualIdentity' => [
            'logoUrl' => [
                'primary' => $brandingData['logo_url_primary'] ?? '',
                'favicon' => $brandingData['logo_url_favicon'] ?? '',
                'socialShare' => $brandingData['logo_url_social_share'] ?? ''
            ],
            'colors' => [
                'background' => $brandingData['color_background'] ?? '#ffffff',
                'primaryText' => $brandingData['color_primary_text'] ?? '#333333',
                'secondaryText' => $brandingData['color_secondary_text'] ?? '#666666',
                'button' => $brandingData['color_button'] ?? '#e0e0e0',
                'buttonText' => $brandingData['color_button_text'] ?? '#333333'
            ]
        ],
        'socialMedia' => [
            'facebook' => $brandingData['social_facebook'] ?? '',
            'twitter' => $brandingData['social_twitter'] ?? '',
            'instagram' => $brandingData['social_instagram'] ?? '',
            'linkedin' => $brandingData['social_linkedin'] ?? '',
            'youtube' => $brandingData['social_youtube'] ?? ''
        ],
        'contactInformation' => [
            'email' => $brandingData['contact_email'] ?? '',
            'phone' => $brandingData['contact_phone'] ?? '',
            'address' => $brandingData['contact_address'] ?? ''
        ]
    ];
} catch(PDOException $e) {
    error_log("Failed to load branding data: " . $e->getMessage());
    // Use default values if database query fails
    $brandingConfig = [
        'companyInfo' => [
            'name' => 'Company Name',
            'tagline' => 'Welcome to the Dashboard',
            'foundedYear' => '',
            'headquartersLocation' => '',
            'websiteUrl' => ''
        ],
        'visualIdentity' => [
            'logoUrl' => [
                'primary' => '',
                'favicon' => '',
                'socialShare' => ''
            ],
            'colors' => [
                'background' => '#ffffff',
                'primaryText' => '#333333',
                'secondaryText' => '#666666',
                'button' => '#e0e0e0',
                'buttonText' => '#333333'
            ]
        ],
        'socialMedia' => [
            'facebook' => '',
            'twitter' => '',
            'instagram' => '',
            'linkedin' => '',
            'youtube' => ''
        ],
        'contactInformation' => [
            'email' => '',
            'phone' => '',
            'address' => ''
        ]
    ];
}

// After loading branding configuration, add:
$navigationConfig = json_decode(file_get_contents('navigation.json'), true);

// Fetch user's full name and admin status from database
$stmt = $conn->prepare("SELECT full_name, is_admin, user_type FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$userName = $user ? $user['full_name'] : 'Guest';
$userType = $user ? $user['user_type'] : 'guest';
$isAdmin = $user && $user['is_admin'] ? true : false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($brandingConfig['companyInfo']['name']); ?> - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($brandingConfig['visualIdentity']['logoUrl']['favicon']); ?>">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($brandingConfig['visualIdentity']['logoUrl']['favicon']); ?>">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: <?php echo $brandingConfig['visualIdentity']['colors']['background']; ?>;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideIn {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
            100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
        }
        .page {
            display: none;
            animation: fadeIn 0.5s ease;
        }
        .page.active {
            display: block;
        }
        .sidebar {
            background: linear-gradient(135deg, #ffffff 0%, #f0f4f8 100%);
            box-shadow: 0 0 35px rgba(0, 0, 0, 0.1);
            z-index: 100;
            display: flex;
            flex-direction: column;
            height: 100vh;
            position: relative;
        }
        .sidebar-content {
            flex: 1;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(203, 213, 225, 0.5) transparent;
            padding-bottom: 100px;
        }
        .sidebar-content::-webkit-scrollbar {
            width: 5px;
        }
        .sidebar-content::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar-content::-webkit-scrollbar-thumb {
            background-color: rgba(203, 213, 225, 0.5);
            border-radius: 20px;
        }
        .sidebar-content::-webkit-scrollbar-thumb:hover {
            background-color: rgba(148, 163, 184, 0.7);
        }
        .sidebar-item {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 12px;
            margin: 8px 12px;
            position: relative;
            overflow: hidden;
            color: <?php echo $brandingConfig['visualIdentity']['colors']['primaryText']; ?>;
        }
        .sidebar-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: <?php echo $brandingConfig['visualIdentity']['colors']['button']; ?>;
            opacity: 0;
            transition: all 0.3s ease;
        }
        .sidebar-item:hover {
            background-color: <?php echo $brandingConfig['visualIdentity']['colors']['button']; ?>;
            color: <?php echo $brandingConfig['visualIdentity']['colors']['buttonText']; ?>;
            transform: translateX(3px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }
        .sidebar-item:hover::before {
            opacity: 1;
        }
        .sidebar-item.active {
            background: <?php echo $brandingConfig['visualIdentity']['colors']['button']; ?>;
            color: <?php echo $brandingConfig['visualIdentity']['colors']['buttonText']; ?>;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }
        .sidebar-item.active::before {
            opacity: 1;
        }
        .sidebar-item i {
            transition: all 0.3s ease;
        }
        .sidebar-item:hover i {
            transform: translateX(2px) scale(1.1);
            color: <?php echo $brandingConfig['visualIdentity']['colors']['buttonText']; ?>;
        }
        .sidebar-item.active i {
            color: <?php echo $brandingConfig['visualIdentity']['colors']['buttonText']; ?>;
        }
        /* Dropdown menu styles */
        .dropdown-container {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            margin-left: 20px;
        }
        .dropdown-container.open {
            max-height: 500px; /* Adjust based on your needs */
            overflow-y: auto;
        }
        /* For very large dropdowns, allow scrolling within the dropdown */
        .dropdown-container.open.large {
            max-height: 300px; /* Limit height for very large dropdowns */
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(203, 213, 225, 0.5) transparent;
        }
        .dropdown-container.open.large::-webkit-scrollbar {
            width: 4px;
        }
        .dropdown-container.open.large::-webkit-scrollbar-track {
            background: transparent;
        }
        .dropdown-container.open.large::-webkit-scrollbar-thumb {
            background-color: rgba(203, 213, 225, 0.5);
            border-radius: 20px;
        }
        .dropdown-toggle {
            cursor: pointer;
            position: relative;
            background-color: rgba(240, 240, 240, 0.5);
        }
        .dropdown-toggle .fa-chevron-right {
            transition: transform 0.3s ease;
        }
        .dropdown-toggle.open .fa-chevron-right {
            transform: rotate(90deg);
        }
        .dropdown-toggle.clicked {
            background-color: <?php echo $brandingConfig['visualIdentity']['colors']['button']; ?>;
            color: <?php echo $brandingConfig['visualIdentity']['colors']['buttonText']; ?>;
            font-weight: 600;
        }
        .dropdown-toggle.clicked .fa-chevron-right {
            transform: rotate(90deg);
            color: <?php echo $brandingConfig['visualIdentity']['colors']['buttonText']; ?>;
        }
        .dropdown-item {
            padding-left: 15px;
            margin-top: 5px;
            margin-bottom: 5px;
        }
        /* Hover effect for dropdown toggle */
        .dropdown-toggle:hover {
            background-color: <?php echo $brandingConfig['visualIdentity']['colors']['button']; ?>;
            color: <?php echo $brandingConfig['visualIdentity']['colors']['buttonText']; ?>;
        }
        /* End dropdown styles */
        .sidebar-logo {
            transition: all 0.3s ease;
        }
        .sidebar-logo:hover {
            transform: scale(1.05);
        }
        .menu-toggle {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 200;
            transition: all 0.3s ease;
            background: white;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .menu-toggle:hover {
            transform: rotate(90deg);
            background: #3b82f6;
            color: white;
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.3);
        }
        .user-profile {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-top: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 0 0 12px 12px;
            transition: all 0.3s ease;
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
        }
        .user-profile:hover {
            background: linear-gradient(135deg, #f1f5f9 0%, #dbeafe 100%);
        }
        .logout-btn {
            transition: all 0.3s ease;
            border-radius: 8px;
            padding: 6px 12px;
        }
        .logout-btn:hover {
            background-color: rgba(239, 68, 68, 0.1);
            transform: translateY(-2px);
        }
        iframe {
            width: 100%;
            height: calc(100vh - 40px);
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        iframe:hover {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }
        @media (max-width: 1023px) {
            .sidebar.open {
                animation: slideIn 0.3s forwards;
                height: 100vh;
                overflow: hidden;
                display: flex;
                flex-direction: column;
            }
            .sidebar-content {
                max-height: calc(100vh - 200px); /* Adjust based on header and footer height */
                overflow-y: auto;
            }
            .user-profile {
                position: absolute;
                bottom: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body class="bg-[<?php echo $brandingConfig['visualIdentity']['colors']['background']; ?>]">
    <div class="flex">
        <!-- Sidebar for desktop -->
        <div id="sidebar" class="sidebar hidden lg:block w-72 h-screen fixed left-0 top-0 transition-all duration-300 ease-in-out">
            <div class="p-6 text-center">
                <div class="sidebar-logo inline-block mb-6 relative">
                    <img src="<?php echo htmlspecialchars($brandingConfig['visualIdentity']['logoUrl']['primary']); ?>" 
                         alt="<?php echo htmlspecialchars($brandingConfig['companyInfo']['name']); ?>" class="mx-auto w-36 h-auto drop-shadow-md">
                </div>
                <p class="text-gray-500 text-xs font-medium tracking-wider uppercase mt-2">
                    <?php echo htmlspecialchars($brandingConfig['companyInfo']['tagline']); ?>
                </p>
            </div>
            
            <div class="sidebar-content">
                <div class="mt-4 mb-16">
                    <ul id="nav-links" class="space-y-1">
                        <!-- Navigation items will be dynamically added here -->
                    </ul>
                </div>
            </div>
            
            <div class="user-profile p-5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold text-sm overflow-hidden mr-3">
                            <span id="user-initials">JD</span>
                        </div>
                        <div>
                            <p class="text-sm font-semibold" id="username" style="color: <?php echo $brandingConfig['visualIdentity']['colors']['primaryText']; ?>">
                                <?php echo htmlspecialchars($userName); ?>
                            </p>
                            <div class="flex items-center">
                                <span class="w-2 h-2 rounded-full bg-green-500 mr-1"></span>
                                <p class="text-xs" id="plan-level" style="color: <?php echo $brandingConfig['visualIdentity']['colors']['secondaryText']; ?>">
                                    <?php echo htmlspecialchars($userType); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <button id="logout-btn" class="logout-btn text-sm text-red-500 hover:text-red-700 flex items-center">
                        <i class="fas fa-sign-out-alt mr-1"></i>
                        <span class="hidden md:inline">Logout</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Hamburger menu for mobile -->
        <button id="menu-toggle" class="menu-toggle lg:hidden">
            <i class="fas fa-bars text-blue-500"></i>
        </button>

        <!-- Main content -->
        <div class="flex-1 lg:ml-72">
            <!-- Content area -->
            <div class="p-6 mt-10 lg:mt-0" id="content-area">
                <!-- Pages will be dynamically loaded here -->
            </div>
        </div>
    </div>

    <script>
        // Menu toggle functionality
        const menuToggle = document.getElementById('menu-toggle');
        const sidebar = document.getElementById('sidebar');
        
        function closeMobileMenu() {
            if (window.innerWidth < 1024) {
                sidebar.classList.add('hidden');
                sidebar.classList.remove('open');
                menuToggle.innerHTML = '<i class="fas fa-bars text-blue-500"></i>';
                menuToggle.style.transform = 'rotate(0deg)';
                menuToggle.style.background = 'white';
            }
        }
        
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('hidden');
            sidebar.classList.toggle('open');
            
            // Animation effect for menu toggle
            if (!sidebar.classList.contains('hidden')) {
                menuToggle.innerHTML = '<i class="fas fa-times text-white"></i>';
                menuToggle.style.transform = 'rotate(90deg)';
                menuToggle.style.background = '<?php echo $brandingConfig['visualIdentity']['colors']['button']; ?>';
                
                // Ensure the logo is visible in the mobile sidebar
                if (window.innerWidth < 1024) {
                    // Make sure the logo container exists
                    let logoContainer = sidebar.querySelector('.p-6.text-center');
                    if (!logoContainer) {
                        // Create the logo container with the same structure as desktop
                        const logoUrl = <?php echo json_encode(htmlspecialchars($brandingConfig['visualIdentity']['logoUrl']['primary'])); ?>;
                        const companyName = <?php echo json_encode(htmlspecialchars($brandingConfig['companyInfo']['name'])); ?>;
                        const tagline = <?php echo json_encode(htmlspecialchars($brandingConfig['companyInfo']['tagline'])); ?>;
                        
                        logoContainer = document.createElement('div');
                        logoContainer.className = 'p-6 text-center';
                        logoContainer.innerHTML = `
                            <div class="sidebar-logo inline-block mb-6 relative">
                                <img src="${logoUrl}" 
                                     alt="${companyName}" class="mx-auto w-36 h-auto drop-shadow-md">
                            </div>
                            <p class="text-gray-500 text-xs font-medium tracking-wider uppercase mt-2">
                                ${tagline}
                            </p>
                        `;
                        sidebar.insertBefore(logoContainer, sidebar.firstChild);
                    }
                }
            } else {
                closeMobileMenu();
            }
        });

        // Navigation setup
        const navLinks = document.getElementById('nav-links');
        const contentArea = document.getElementById('content-area');

        function hasPermission(permissions) {
            const userType = <?php echo json_encode($userType); ?>;
            const isAdmin = <?php echo json_encode($isAdmin); ?>;
            
            // Admins always have access
            if (isAdmin) return true;
            
            // Check if user type matches any of the required permissions
            if (Array.isArray(permissions)) {
                return permissions.includes(userType);
            } else if (typeof permissions === 'string') {
                return permissions === userType;
            }
            
            return false;
        }

        function createNavigationSection(section) {
            // Skip section if user doesn't have permission
            if (!hasPermission(section.permissions)) {
                return null;
            }

            const sectionDiv = document.createElement('div');
            sectionDiv.className = 'nav-section mb-4';

            // Create items container
            const itemsContainer = document.createElement('div');
            itemsContainer.className = 'nav-items';

            // Create dropdown toggle using section title
            if (section.title) {
                const dropdownToggle = document.createElement('li');
                dropdownToggle.className = 'sidebar-item dropdown-toggle py-3 px-6 cursor-pointer flex items-center justify-between';
                
                // Create icon and title container
                const leftContent = document.createElement('div');
                leftContent.className = 'flex items-center';
                
                // Use appropriate icon based on section title
                let iconClass = section.icon || 'folder';
                
                const iconContainer = document.createElement('div');
                iconContainer.className = 'w-8 h-8 rounded-lg flex items-center justify-center mr-3 bg-opacity-10';
                iconContainer.innerHTML = `<i class="fas fa-${iconClass} text-lg"></i>`;
                
                const titleSpan = document.createElement('span');
                titleSpan.textContent = section.title;
                titleSpan.className = 'flex-1 font-semibold uppercase text-xs tracking-wider';
                
                leftContent.appendChild(iconContainer);
                leftContent.appendChild(titleSpan);
                
                // Create chevron icon
                const chevron = document.createElement('i');
                chevron.className = 'fas fa-chevron-right transition-transform';
                
                dropdownToggle.appendChild(leftContent);
                dropdownToggle.appendChild(chevron);
                
                // Create dropdown container
                const dropdownContainer = document.createElement('ul');
                dropdownContainer.className = 'dropdown-container';
                
                // Add all section items to the dropdown
                section.items.forEach(item => {
                    const navItem = createNavItem(item, true);
                    dropdownContainer.appendChild(navItem);
                });
                
                // Add 'large' class to dropdowns with many items
                if (section.items.length > 8) {
                    dropdownContainer.classList.add('large');
                }
                
                // Toggle dropdown on click
                dropdownToggle.addEventListener('click', () => {
                    dropdownToggle.classList.toggle('open');
                    dropdownContainer.classList.toggle('open');
                });
                
                // Expand dropdown on hover
                dropdownToggle.addEventListener('mouseenter', () => {
                    dropdownToggle.classList.add('open');
                    dropdownContainer.classList.add('open');
                });
                
                // Keep dropdown open when hovering over dropdown items
                dropdownContainer.addEventListener('mouseenter', () => {
                    dropdownToggle.classList.add('open');
                    dropdownContainer.classList.add('open');
                });
                
                // Optional: Close dropdown when mouse leaves both toggle and container
                const closeDropdown = (e) => {
                    // Only close if we're not hovering over the toggle or container
                    if (!dropdownToggle.contains(e.relatedTarget) && !dropdownContainer.contains(e.relatedTarget)) {
                        // Don't close if it was explicitly clicked open
                        if (!dropdownToggle.classList.contains('clicked')) {
                            dropdownToggle.classList.remove('open');
                            dropdownContainer.classList.remove('open');
                        }
                    }
                };
                
                dropdownToggle.addEventListener('mouseleave', closeDropdown);
                dropdownContainer.addEventListener('mouseleave', closeDropdown);
                
                // Mark as clicked when toggle is clicked
                dropdownToggle.addEventListener('click', () => {
                    dropdownToggle.classList.toggle('clicked');
                    // If we're closing it by clicking, remove the clicked state
                    if (!dropdownToggle.classList.contains('open')) {
                        dropdownToggle.classList.remove('clicked');
                    }
                });
                
                itemsContainer.appendChild(dropdownToggle);
                itemsContainer.appendChild(dropdownContainer);
            } else {
                // If no section title, add items directly
                section.items.forEach(item => {
                    const navItem = createNavItem(item);
                    itemsContainer.appendChild(navItem);
                });
            }

            sectionDiv.appendChild(itemsContainer);
            return sectionDiv;
        }

        function createNavItem(item, isDropdownItem = false) {
            const navItem = document.createElement('li');
            navItem.className = isDropdownItem ? 
                'sidebar-item dropdown-item py-2 px-4 cursor-pointer flex items-center' : 
                'sidebar-item py-3 px-6 cursor-pointer flex items-center';
            navItem.setAttribute('data-page', item.id);
            navItem.setAttribute('data-url', item.url);
            
            // Create icon container with special styling
            const iconContainer = document.createElement('div');
            iconContainer.className = 'w-8 h-8 rounded-lg flex items-center justify-center mr-3 bg-opacity-10';
            iconContainer.innerHTML = `<i class="fas fa-${item.icon} text-lg"></i>`;
            
            // Create title element
            const titleSpan = document.createElement('span');
            titleSpan.textContent = item.title;
            titleSpan.className = 'flex-1';
            
            navItem.appendChild(iconContainer);
            navItem.appendChild(titleSpan);
            
            navItem.addEventListener('click', (e) => {
                e.stopPropagation(); // Prevent event bubbling to parent dropdown
                document.querySelectorAll('.sidebar-item').forEach(i => {
                    if (!i.classList.contains('dropdown-toggle')) {
                        i.classList.remove('active');
                    }
                });
                navItem.classList.add('active');
                
                // Keep parent dropdown open
                const parentDropdown = navItem.closest('.dropdown-container');
                if (parentDropdown) {
                    parentDropdown.classList.add('open');
                    const dropdownToggle = parentDropdown.previousElementSibling;
                    if (dropdownToggle && dropdownToggle.classList.contains('dropdown-toggle')) {
                        dropdownToggle.classList.add('open');
                        dropdownToggle.classList.add('clicked'); // Mark as clicked to keep it open
                    }
                }
                
                loadPage(item.id, item.url);
                
                // Close mobile menu when a page is selected
                closeMobileMenu();
            });

            return navItem;
        }

        // Load navigation configuration
        const navigationConfig = <?php echo json_encode($navigationConfig); ?>;
        
        // Load default navigation sections
        navigationConfig.default.sections.forEach(section => {
            const sectionElement = createNavigationSection(section);
            if (sectionElement) {
                navLinks.appendChild(sectionElement);
            }
        });

        <?php if ($isAdmin): ?>
        // Load admin navigation sections
        navigationConfig.admin.sections.forEach(section => {
            const sectionElement = createNavigationSection(section);
            if (sectionElement) {
                navLinks.appendChild(sectionElement);
            }
        });
        <?php endif; ?>

        // Function to load page content
        function loadPage(id, url) {
            contentArea.innerHTML = '';
            const loadingIndicator = document.createElement('div');
            loadingIndicator.className = 'flex items-center justify-center h-64';
            loadingIndicator.innerHTML = `
                <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
            `;
            contentArea.appendChild(loadingIndicator);
            
            // Keep parent dropdown open if this page is inside a dropdown
            const activeItem = document.querySelector(`.sidebar-item[data-page="${id}"]:not(.dropdown-toggle)`);
            if (activeItem) {
                const parentDropdown = activeItem.closest('.dropdown-container');
                if (parentDropdown) {
                    parentDropdown.classList.add('open');
                    const dropdownToggle = parentDropdown.previousElementSibling;
                    if (dropdownToggle && dropdownToggle.classList.contains('dropdown-toggle')) {
                        dropdownToggle.classList.add('open');
                        dropdownToggle.classList.add('clicked'); // Mark as clicked to keep it open
                    }
                }
            }
            
            setTimeout(() => {
                contentArea.innerHTML = `<iframe src="${url}" id="${id}-frame"></iframe>`;
                // Update URL with clean query parameter
                const cleanTitle = id.toLowerCase().replace(/[^a-z0-9]/g, '-');
                history.pushState({ id, url }, '', `dashboard.php?page=${cleanTitle}`);
                
                // Close mobile menu when page loads
                closeMobileMenu();
            }, 500);
        }

        // Function to load first available page
        function loadFirstAvailablePage() {
            // Find first visible navigation item that's not a dropdown toggle
            const firstNavItem = document.querySelector('.sidebar-item:not(.dropdown-toggle)');
            if (firstNavItem) {
                const pageId = firstNavItem.getAttribute('data-page');
                const pageUrl = firstNavItem.getAttribute('data-url');
                firstNavItem.classList.add('active');
                loadPage(pageId, pageUrl);
            } else {
                // If all items are in dropdowns, open the first dropdown and select its first item
                const firstDropdown = document.querySelector('.dropdown-toggle');
                if (firstDropdown) {
                    firstDropdown.classList.add('open');
                    const dropdownContainer = firstDropdown.nextElementSibling;
                    dropdownContainer.classList.add('open');
                    
                    const firstDropdownItem = dropdownContainer.querySelector('.sidebar-item');
                    if (firstDropdownItem) {
                        const pageId = firstDropdownItem.getAttribute('data-page');
                        const pageUrl = firstDropdownItem.getAttribute('data-url');
                        firstDropdownItem.classList.add('active');
                        loadPage(pageId, pageUrl);
                    }
                }
            }
        }

        // Handle browser back/forward buttons
        window.addEventListener('popstate', (event) => {
            if (event.state) {
                const { id, url } = event.state;
                
                // Update active state in sidebar
                document.querySelectorAll('.sidebar-item').forEach(item => {
                    if (!item.classList.contains('dropdown-toggle')) {
                        item.classList.toggle('active', item.getAttribute('data-page') === id);
                        
                        // If this item is now active and inside a dropdown, keep the dropdown open
                        if (item.getAttribute('data-page') === id) {
                            const parentDropdown = item.closest('.dropdown-container');
                            if (parentDropdown) {
                                parentDropdown.classList.add('open');
                                const dropdownToggle = parentDropdown.previousElementSibling;
                                if (dropdownToggle && dropdownToggle.classList.contains('dropdown-toggle')) {
                                    dropdownToggle.classList.add('open');
                                    dropdownToggle.classList.add('clicked'); // Mark as clicked to keep it open
                                }
                            }
                        }
                    }
                });
                
                contentArea.innerHTML = `<iframe src="${url}" id="${id}-frame"></iframe>`;
            }
        });

        // Responsive sidebar
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                sidebar.classList.remove('hidden');
            } else {
                sidebar.classList.add('hidden');
            }
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (event) => {
            const isClickInsideSidebar = sidebar.contains(event.target);
            const isClickOnMenuToggle = menuToggle.contains(event.target);

            if (!isClickInsideSidebar && !isClickOnMenuToggle && window.innerWidth < 1024) {
                closeMobileMenu();
            }
        });

        // Logout functionality
        const logoutBtn = document.getElementById('logout-btn');
        logoutBtn.addEventListener('click', () => {
            window.location.href = 'logout.php';
        });

        // Update user info in the sidebar
        document.getElementById('username').textContent = <?php echo json_encode($userName); ?>;
        document.getElementById('plan-level').textContent = <?php echo json_encode($userType); ?>;
        
        // Set user initials
        const initials = <?php echo json_encode($userName); ?>.split(' ').map(n => n[0]).join('');
        document.getElementById('user-initials').textContent = initials;
        
        // Update document title dynamically
        document.title = <?php echo json_encode($brandingConfig['companyInfo']['name']); ?> + ' - Dashboard';

        // Update favicon dynamically
        const setFavicon = (url) => {
            const favicon = document.querySelector('link[rel="icon"]');
            const appleTouchIcon = document.querySelector('link[rel="apple-touch-icon"]');
            favicon.href = url;
            appleTouchIcon.href = url;
        };
        setFavicon(<?php echo json_encode($brandingConfig['visualIdentity']['logoUrl']['favicon']); ?>);

        // Check for page parameter on load
        const urlParams = new URLSearchParams(window.location.search);
        const pageParam = urlParams.get('page');
        
        if (pageParam) {
            // Find matching navigation item
            const matchingItem = document.querySelector(`.sidebar-item[data-page="${pageParam}"]:not(.dropdown-toggle)`);
            if (matchingItem) {
                const pageId = matchingItem.getAttribute('data-page');
                const pageUrl = matchingItem.getAttribute('data-url');
                
                // If item is in a dropdown, open the dropdown
                const parentDropdown = matchingItem.closest('.dropdown-container');
                if (parentDropdown) {
                    parentDropdown.classList.add('open');
                    const dropdownToggle = parentDropdown.previousElementSibling;
                    if (dropdownToggle && dropdownToggle.classList.contains('dropdown-toggle')) {
                        dropdownToggle.classList.add('open');
                        dropdownToggle.classList.add('clicked'); // Mark as clicked to keep it open
                    }
                }
                
                document.querySelectorAll('.sidebar-item').forEach(i => {
                    if (!i.classList.contains('dropdown-toggle')) {
                        i.classList.remove('active');
                    }
                });
                matchingItem.classList.add('active');
                loadPage(pageId, pageUrl);
            } else {
                loadFirstAvailablePage();
            }
        } else {
            loadFirstAvailablePage();
        }

        // Add swipe gesture to close sidebar on mobile
        let touchStartX = 0;
        let touchEndX = 0;
        
        sidebar.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        }, false);
        
        sidebar.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, false);
        
        function handleSwipe() {
            if (window.innerWidth < 1024) {
                // Swipe left (close menu)
                if (touchEndX < touchStartX - 50) {
                    closeMobileMenu();
                }
            }
        }
        
        // Remove the mobile close button functionality
        /*
        // Add close button at the top of mobile sidebar
        function addMobileCloseButton() {
            if (window.innerWidth < 1024) {
                // Check if close button already exists
                if (!document.getElementById('mobile-close-btn')) {
                    const closeBtn = document.createElement('button');
                    closeBtn.id = 'mobile-close-btn';
                    closeBtn.className = 'absolute top-4 right-4 z-50 w-8 h-8 flex items-center justify-center rounded-full bg-gray-200 text-gray-600 hover:bg-gray-300';
                    closeBtn.innerHTML = '<i class="fas fa-times"></i>';
                    closeBtn.addEventListener('click', closeMobileMenu);
                    
                    // Insert at the beginning of sidebar
                    sidebar.insertBefore(closeBtn, sidebar.firstChild);
                }
            } else {
                // Remove close button on desktop
                const closeBtn = document.getElementById('mobile-close-btn');
                if (closeBtn) {
                    closeBtn.remove();
                }
            }
        }
        
        // Add close button when sidebar is opened
        menuToggle.addEventListener('click', () => {
            if (!sidebar.classList.contains('hidden')) {
                setTimeout(addMobileCloseButton, 100);
            }
        });
        
        // Update close button on resize
        window.addEventListener('resize', addMobileCloseButton);
        
        // Initial setup
        addMobileCloseButton();
        */
        
        // Remove any existing close buttons
        const existingCloseBtn = document.getElementById('mobile-close-btn');
        if (existingCloseBtn) {
            existingCloseBtn.remove();
        }
    </script>
</body>
</html>
