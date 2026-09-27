<nav id="sidebar">
    <?php
    // Soo qaado xogta Settings-ka si Sidebar-ka iskiis isu beddelo
    $sidebar_settings = [];
    if (isset($conn)) {
        $set_query = $conn->query("SELECT hotel_name, hotel_logo FROM settings WHERE id = 1");
        if ($set_query && $set_query->num_rows > 0) {
            $sidebar_settings = $set_query->fetch_assoc();
        }
    }
    $hotel_title = !empty($sidebar_settings['hotel_name']) ? htmlspecialchars($sidebar_settings['hotel_name']) : 'Afrik Hotel';
    $hotel_logo = !empty($sidebar_settings['hotel_logo']) ? htmlspecialchars($sidebar_settings['hotel_logo']) : '';
    ?>
    
    <!-- Halkan ayaan ka dhignay inuu kor iyo hoos u istaago (flex-direction: column) -->
    <div class="sidebar-header" style="flex-direction: column; text-align: center; padding-top: 35px; padding-bottom: 25px;">
        
        <!-- Sawirka oo laga dhigay Goobaabin (Round) 50% -->
        <?php if ($hotel_logo): ?>
            <img src="<?php echo $basePath; ?>assets/images/<?php echo $hotel_logo; ?>" alt="Logo" style="width: 80px; height: 80px; object-fit: cover; margin-bottom: 15px; margin-right: 0; background: #fff; border-radius: 50%; padding: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
        <?php else: ?>
            <!-- Haddii uusan sawir jirin, Icon-ka isna goobaabin ayuu ku dhex jirayaa -->
            <div style="width: 80px; height: 80px; background: rgba(16, 185, 129, 0.15); color: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto; border: 2px solid #10b981;">
                <i class="fas fa-hotel" style="font-size: 2rem;"></i>
            </div>
        <?php endif; ?>
        
        <!-- Magaca Hotelka -->
        <div>
            <div style="font-size: 1.25rem; line-height: 1.2; font-weight: 700; color: #fff;"><?php echo $hotel_title; ?></div>
            <div style="font-size: 0.8rem; font-weight: normal; color: #8a99af; margin-top: 5px;">Management System</div>
        </div>
    </div>
        
    <?php 
    $current_page = basename($_SERVER['PHP_SELF']);
         
    // Fetch and group allowed pages from the database
    $menu_structure = [];
    if (isset($conn)) {
        $pages_query = $conn->query("SELECT * FROM system_pages ORDER BY category ASC, id ASC");
                 
        while($row = $pages_query->fetch_assoc()) {
            if (hasPermission($row['url'])) {
                if ($row['category'] === 'Finance' && (stripos($row['page_name'], 'report') !== false || stripos($row['url'], 'report') !== false)) {
                    $menu_structure['Reports'][] = $row;
                } else {
                    $menu_structure[$row['category']][] = $row;
                }
            }
        }
        
        // Habaynta kala horreynta (Explicit Ordering): 
        // 1. Main wuxuu aadaa kor
        // 2. Qaybaha kale dhexda
        // 3. System ayaa xiga
        // 4. Reports ayaa ugu dambeeya kahor Logout-ka
        $ordered_menu = [];
        
        if (isset($menu_structure['Main'])) {
            $ordered_menu['Main'] = $menu_structure['Main'];
            unset($menu_structure['Main']);
        }
        
        $system_pages = isset($menu_structure['System']) ? $menu_structure['System'] : null;
        unset($menu_structure['System']);
        
        $reports_pages = isset($menu_structure['Reports']) ? $menu_structure['Reports'] : null;
        unset($menu_structure['Reports']);
        
        // Ku dar qaybahaharay (Finance, Rooms, iwm) dhexda
        foreach ($menu_structure as $cat => $pages) {
            $ordered_menu[$cat] = $pages;
        }
        
        // Hadda System geli
        if ($system_pages) {
            $ordered_menu['System'] = $system_pages;
        }
        
        // Ugu dambeyn Reports geli (System ka dib)
        if ($reports_pages) {
            $ordered_menu['Reports'] = $reports_pages;
        }
        
        $menu_structure = $ordered_menu;
    }
    ?>
    <ul class="list-unstyled components">
        <?php foreach($menu_structure as $category => $cat_pages): ?>
                     
            <?php if ($category === 'Main'): ?>
                <!-- Standalone Main Links (Like Dashboard) -->
                <?php foreach($cat_pages as $page): ?>
                    <li class="<?php echo ($current_page == basename($page['url'])) ? 'active' : ''; ?>">
                        <a href="<?php echo $basePath . $page['url']; ?>" class="d-flex align-items-center">
                            <i class="<?php echo htmlspecialchars($page['icon_class']); ?> mr-3" style="width: 20px; text-align: center;"></i> 
                            <span style="white-space: nowrap;"><?php echo htmlspecialchars($page['page_name']); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
                            
            <?php else: ?>
                <!-- Dropdown Categories -->
                <?php $cat_id = md5($category); ?>
                <li>
                    <a href="#cat-<?php echo $cat_id; ?>" data-toggle="collapse" aria-expanded="false" class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <?php if ($category === 'Reports'): ?>
                                <i class="fas fa-chart-bar text-muted mr-3" style="width: 20px; text-align: center;"></i>
                            <?php elseif ($category === 'System'): ?>
                                <i class="fas fa-cogs text-muted mr-3" style="width: 20px; text-align: center;"></i>
                            <?php else: ?>
                                <i class="fas fa-folder text-muted mr-3" style="width: 20px; text-align: center;"></i>
                            <?php endif; ?>
                            <span style="white-space: nowrap;"><?php echo htmlspecialchars($category); ?></span>
                        </div>
                        <i class="fas fa-chevron-down text-muted" style="font-size: 0.7rem;"></i>
                    </a>
                                         
                    <ul class="collapse list-unstyled" id="cat-<?php echo $cat_id; ?>" style="background: rgba(0, 0, 0, 0.15);">
                        <?php foreach($cat_pages as $page): ?>
                            <li class="<?php echo ($current_page == basename($page['url'])) ? 'active' : ''; ?>">
                                <a href="<?php echo $basePath . $page['url']; ?>" class="d-flex align-items-center" style="padding-left: 45px; font-size: 0.9rem;">
                                    <i class="<?php echo htmlspecialchars($page['icon_class']); ?> mr-3 text-muted" style="width: 15px; text-align: center;"></i> 
                                    <span style="white-space: nowrap;"><?php echo htmlspecialchars($page['page_name']); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endif; ?>
             
        <?php endforeach; ?>
    </ul>
    
    <div class="logout-container mt-auto">
        <ul class="list-unstyled mb-0">
            <li>
                <a href="<?php echo $basePath; ?>logout.php" class="d-flex align-items-center">
                    <i class="fas fa-sign-out-alt mr-3" style="width: 20px; text-align: center;"></i> 
                    <span>Logout</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $basePath; ?>admin/dashboard/profile.php" class="text-muted mt-2 d-flex align-items-center">
                    <i class="fas fa-user-secret mr-3" style="width: 20px; text-align: center;"></i> 
                    <span>Profile</span>
                </a>
            </li>
        </ul>
    </div>
</nav>