#
# Copyright (C) 2008 OpenWrt.org
#
# This is free software, licensed under the GNU General Public License v2.
# See /LICENSE for more information.
#
# $Id$
CONFIG_PACKAGE_usb4all:=m

include $(TOPDIR)/rules.mk

PKG_NAME:=usb4all
PKG_RELEASE:=1

include $(INCLUDE_DIR)/package.mk

define Package/usb4all
	SECTION:=utils
	CATEGORY:=Utilities
	TITLE:=usb4all -- reads usb4all analog input device
	DEPENDS:=+kmod-usb-acm
endef

define Build/Prepare
	mkdir -p $(PKG_BUILD_DIR)
	$(CP) ./src/* $(PKG_BUILD_DIR)/
endef

define Build/Configure
endef

define Build/Compile
	$(MAKE) -C $(PKG_BUILD_DIR) $(TARGET_CONFIGURE_OPTS)
endef

define Package/usb4all/install
	$(INSTALL_DIR) $(1)/usr/bin
	$(INSTALL_BIN) $(PKG_BUILD_DIR)/usb4all $(1)/usr/bin/
endef

wlinst:	
	cp /home/rdh/src/openwrt/sdk/build_dir/target-mipsel_uClibc-0.9.30.1/usb4all/ipkg-brcm-2.4/usb4all/bin/usb4all /home/rdh/wl
	cp /home/rdh/src/openwrt/sdk/bin/brcm-2.4/packages/usb4all_1_brcm-2.4.ipk /home/rdh/wl
	scp /home/rdh/wl/usb4all milch:/usr/bin

$(eval $(call BuildPackage,usb4all))
