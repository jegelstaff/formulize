#
# Dumping data for table `bannerclient`
#

INSERT INTO bannerclient VALUES (0, 'ImpressCMS', 'ImpressCMS Dev Team', 'info@impresscms.org', '', '', '');

#
# Dumping data for table `bannerfinish`
#

# Adding dynamic block area/position system - TheRpLima - 2007-10-21
#
# Dumping data for table `block_positions`
#
INSERT INTO `block_positions` VALUES (1,'canvas_left','_AM_SBLEFT',NULL,1,'L')
, (2,'canvas_right','_AM_SBRIGHT',NULL,1,'L')
, (3,'page_topleft','_AM_CBLEFT',NULL,1,'C')
, (4,'page_topcenter','_AM_CBCENTER',NULL,1,'C')
, (5,'page_topright','_AM_CBRIGHT',NULL,1,'C')
, (6,'page_bottomleft','_AM_CBBOTTOMLEFT',NULL,1,'C')
, (7,'page_bottomcenter','_AM_CBBOTTOM',NULL,1,'C')
, (8,'page_bottomright','_AM_CBBOTTOMRIGHT',NULL,1,'C')
, (9,'canvas_left_admin','_AM_SBLEFT_ADMIN',NULL,1,'L')
, (10,'canvas_right_admin','_AM_SBRIGHT_ADMIN',NULL,1,'L')
, (11,'page_topleft_admin','_AM_CBLEFT_ADMIN',NULL,1,'C')
, (12,'page_topcenter_admin','_AM_CBCENTER_ADMIN',NULL,1,'C')
, (13,'page_topright_admin','_AM_CBRIGHT_ADMIN',NULL,1,'C')
, (14,'page_bottomleft_admin','_AM_CBBOTTOMLEFT_ADMIN',NULL,1,'C')
, (15,'page_bottomcenter_admin','_AM_CBBOTTOM_ADMIN',NULL,1,'C')
, (16,'page_bottomright_admin','_AM_CBBOTTOMRIGHT_ADMIN',NULL,1,'C');

#
# Dumping data for table `comments`
#


#
# Dumping data for table `configcategory`
#

INSERT INTO configcategory VALUES (1, '_MD_AM_GENERAL', 0)
, (2, '_MD_AM_USERSETTINGS', 0)
, (3, '_MD_AM_METAFOOTER', 0)
, (4, '_MD_AM_CENSOR', 0)
, (5, '_MD_AM_SEARCH', 0)
, (6, '_MD_AM_MAILER', 0)
, (7, '_MD_AM_AUTHENTICATION', 0)
, (8, '_MD_AM_MULTILANGUAGE', 0)
, (10, '_MD_AM_PERSON', 0)
, (11, '_MD_AM_CAPTCHA', 0)
, (12, '_MD_AM_PLUGINS', 0)
, (13, '_MD_AM_AUTOTASKS', 0)
, (14, '_MD_AM_PURIFIER', 0);

#
# Dumping data for table `image`
#


#
# Dumping data for table `imagebody`
#


#
# Dumping data for table `imagecategory`
#


#
# Dumping data for table `imgset`
#

INSERT INTO imgset VALUES (1, 'default', 0);

#
# Dumping data for table `imgset_tplset_link`
#

INSERT INTO imgset_tplset_link VALUES (1, 'default');

#
# Dumping data for table `online`
#


#
# Dumping data for table `priv_msgs`
#


#
# Dumping data for table `session`
#


#
# Dumping data for table `system_mimetype`
#

INSERT INTO `system_mimetype` (`mimetypeid`, `extension`, `types`, `name`, `dirname`) VALUES
(1, 'bin', 'application/octet-stream', 'Binary File/Linux Executable', ''),
(2, 'dms', 'application/octet-stream', 'Amiga DISKMASHER Compressed Archive', ''),
(3, 'class', 'application/octet-stream', 'Java Bytecode', ''),
(4, 'so', 'application/octet-stream', 'UNIX Shared Library Function', ''),
(5, 'dll', 'application/octet-stream', 'Dynamic Link Library', ''),
(6, 'hqx', 'application/binhex application/mac-binhex application/mac-binhex40', 'Macintosh BinHex 4 Compressed Archive', ''),
(7, 'cpt', 'application/mac-compactpro application/compact_pro', 'Compact Pro Archive', ''),
(8, 'lha', 'application/lha application/x-lha application/octet-stream application/x-compress application/x-compressed application/maclha', 'Compressed Archive File', ''),
(9, 'lzh', 'application/lzh application/x-lzh application/x-lha application/x-compress application/x-compressed application/x-lzh-archive zz-application/zz-winassoc-lzh application/maclha application/octet-stream', 'Compressed Archive File', ''),
(10, 'sh', 'application/x-shar', 'UNIX shar Archive File', ''),
(11, 'shar', 'application/x-shar', 'UNIX shar Archive File', ''),
(12, 'tar', 'application/tar application/x-tar applicaton/x-gtar multipart/x-tar application/x-compress application/x-compressed', 'Tape Archive File', ''),
(13, 'gtar', 'application/x-gtar', 'GNU tar Compressed File Archive', ''),
(14, 'ustar', 'application/x-ustar multipart/x-ustar', 'POSIX tar Compressed Archive', ''),
(15, 'zip', 'application/zip application/x-zip application/x-zip-compressed application/octet-stream application/x-compress application/x-compressed multipart/x-zip', 'Compressed Archive File', ''),
(16, 'exe', 'application/exe application/x-exe application/dos-exe application/x-winexe application/msdos-windows application/x-msdos-program', 'Executable File', ''),
(17, 'wmz', 'application/x-ms-wmz', 'Windows Media Compressed Skin File', ''),
(18, 'wmd', 'application/x-ms-wmd', 'Windows Media Download File', ''),
(19, 'doc', 'application/msword application/doc appl/text application/vnd.msword application/vnd.ms-word application/winword application/word application/x-msw6 application/x-msword', 'Word Document', 'system'),
(20, 'pdf', 'application/pdf application/acrobat application/x-pdf applications/vnd.pdf text/pdf', 'Acrobat Portable Document Format', 'system'),
(21, 'eps', 'application/eps application/postscript application/x-eps image/eps image/x-eps', 'Encapsulated PostScript', ''),
(22, 'ps', 'application/postscript application/ps application/x-postscript application/x-ps text/postscript', 'PostScript', ''),
(23, 'smi', 'application/smil', 'SMIL Multimedia', ''),
(24, 'smil', 'application/smil', 'Synchronized Multimedia Integration Language', ''),
(25, 'wmlc', 'application/vnd.wap.wmlc ', 'Compiled WML Document', ''),
(26, 'wmlsc', 'application/vnd.wap.wmlscriptc', 'Compiled WML Script', ''),
(27, 'vcd', 'application/x-cdlink', 'Virtual CD-ROM CD Image File', ''),
(28, 'pgn', 'application/formstore', 'Picatinny Arsenal Electronic Formstore Form in TIFF Format', ''),
(29, 'cpio', 'application/x-cpio', 'UNIX CPIO Archive', ''),
(30, 'csh', 'application/x-csh', 'Csh Script', ''),
(31, 'dcr', 'application/x-director', 'Shockwave Movie', ''),
(32, 'dir', 'application/x-director', 'Macromedia Director Movie', ''),
(33, 'dxr', 'application/x-director application/vnd.dxr', 'Macromedia Director Protected Movie File', ''),
(34, 'dvi', 'application/x-dvi', 'TeX Device Independent Document', ''),
(35, 'spl', 'application/x-futuresplash', 'Macromedia FutureSplash File', ''),
(36, 'hdf', 'application/x-hdf', 'Hierarchical Data Format File', ''),
(37, 'js', 'application/x-javascript text/javascript', 'JavaScript Source Code', ''),
(38, 'skp', 'application/x-koan application/vnd-koan koan/x-skm application/vnd.koan', 'SSEYO Koan Play File', ''),
(39, 'skd', 'application/x-koan application/vnd-koan koan/x-skm application/vnd.koan', 'SSEYO Koan Design File', ''),
(40, 'skt', 'application/x-koan application/vnd-koan koan/x-skm application/vnd.koan', 'SSEYO Koan Template File', ''),
(41, 'skm', 'application/x-koan application/vnd-koan koan/x-skm application/vnd.koan', 'SSEYO Koan Mix File', ''),
(42, 'latex', 'application/x-latex text/x-latex', 'LaTeX Source Document', ''),
(43, 'nc', 'application/x-netcdf text/x-cdf', 'Unidata netCDF Graphics', ''),
(44, 'cdf', 'application/cdf application/x-cdf application/netcdf application/x-netcdf text/cdf text/x-cdf', 'Channel Definition Format', ''),
(45, 'swf', 'application/x-shockwave-flash application/x-shockwave-flash2-preview application/futuresplash image/vnd.rn-realflash', 'Macromedia Flash Format File', ''),
(46, 'sit', 'application/stuffit application/x-stuffit application/x-sit', 'StuffIt Compressed Archive File', ''),
(47, 'tcl', 'application/x-tcl', 'TCL/TK Language Script', ''),
(48, 'tex', 'application/x-tex', 'LaTeX Source', ''),
(49, 'texinfo', 'application/x-texinfo', 'TeX', ''),
(50, 'texi', 'application/x-texinfo', 'TeX', ''),
(51, 't', 'application/x-troff', 'TAR Tape Archive Without Compression', ''),
(52, 'tr', 'application/x-troff', 'Unix Tape Archive = TAR without compression (tar)', ''),
(53, 'src', 'application/x-wais-source', 'Sourcecode', ''),
(54, 'xhtml', 'application/xhtml+xml', 'Extensible HyperText Markup Language File', ''),
(55, 'xht', 'application/xhtml+xml', 'Extensible HyperText Markup Language File', ''),
(56, 'au', 'audio/basic audio/x-basic audio/au audio/x-au audio/x-pn-au audio/rmf audio/x-rmf audio/x-ulaw audio/vnd.qcelp audio/x-gsm audio/snd', 'ULaw/AU Audio File', ''),
(57, 'XM', 'audio/xm audio/x-xm audio/module-xm audio/mod audio/x-mod', 'Fast Tracker 2 Extended Module', ''),
(58, 'snd', 'audio/basic', 'Macintosh Sound Resource', ''),
(59, 'mid', 'audio/mid audio/m audio/midi audio/x-midi application/x-midi audio/soundtrack', 'Musical Instrument Digital Interface MIDI-sequention Sound', ''),
(60, 'midi', 'audio/mid audio/m audio/midi audio/x-midi application/x-midi', 'Musical Instrument Digital Interface MIDI-sequention Sound', ''),
(61, 'kar', 'audio/midi audio/x-midi audio/mid x-music/x-midi', 'Karaoke MIDI File', ''),
(62, 'mpga', 'audio/mpeg audio/mp3 audio/mgp audio/m-mpeg audio/x-mp3 audio/x-mpeg audio/x-mpg video/mpeg', 'Mpeg-1 Layer3 Audio Stream', ''),
(63, 'mp2', 'video/mpeg audio/mpeg', 'MPEG Audio Stream, Layer II', ''),
(64, 'mp3', 'audio/mpeg audio/x-mpeg audio/mp3 audio/x-mp3 audio/mpeg3 audio/x-mpeg3 audio/mpg audio/x-mpg audio/x-mpegaudio', 'MPEG Audio Stream, Layer III', ''),
(65, 'aif', 'audio/aiff audio/x-aiff sound/aiff audio/rmf audio/x-rmf audio/x-pn-aiff audio/x-gsm audio/x-midi audio/vnd.qcelp', 'Audio Interchange File', ''),
(66, 'aiff', 'audio/aiff audio/x-aiff sound/aiff audio/rmf audio/x-rmf audio/x-pn-aiff audio/x-gsm audio/mid audio/x-midi audio/vnd.qcelp', 'Audio Interchange File', ''),
(67, 'aifc', 'audio/aiff audio/x-aiff audio/x-aifc sound/aiff audio/rmf audio/x-rmf audio/x-pn-aiff audio/x-gsm audio/x-midi audio/mid audio/vnd.qcelp', 'Audio Interchange File', ''),
(68, 'm3u', 'audio/x-mpegurl audio/mpeg-url application/x-winamp-playlist audio/scpls audio/x-scpls', 'MP3 Playlist File', ''),
(69, 'ram', 'audio/x-pn-realaudio audio/vnd.rn-realaudio audio/x-pm-realaudio-plugin audio/x-pn-realvideo audio/x-realaudio video/x-pn-realvideo text/plain', 'RealMedia Metafile', ''),
(70, 'rm', 'application/vnd.rn-realmedia audio/vnd.rn-realaudio audio/x-pn-realaudio audio/x-realaudio audio/x-pm-realaudio-plugin', 'RealMedia Streaming Media', ''),
(71, 'rpm', 'audio/x-pn-realaudio audio/x-pn-realaudio-plugin audio/x-pnrealaudio-plugin video/x-pn-realvideo-plugin audio/x-mpegurl application/octet-stream', 'RealMedia Player Plug-in', ''),
(72, 'ra', 'audio/vnd.rn-realaudio audio/x-pn-realaudio audio/x-realaudio audio/x-pm-realaudio-plugin video/x-pn-realvideo', 'RealMedia Streaming Media', ''),
(73, 'wav', 'audio/wav audio/x-wav audio/wave audio/x-pn-wav', 'Waveform Audio', ''),
(74, 'wax', ' audio/x-ms-wax', 'Windows Media Audio Redirector', ''),
(75, 'wma', 'audio/x-ms-wma video/x-ms-asf', 'Windows Media Audio File', ''),
(76, 'bmp', 'image/bmp image/x-bmp image/x-bitmap image/x-xbitmap image/x-win-bitmap image/x-windows-bmp image/ms-bmp image/x-ms-bmp application/bmp application/x-bmp application/x-win-bitmap application/preview', 'Windows OS/2 Bitmap Graphics', 'system'),
(77, 'gif', 'image/gif image/x-xbitmap image/gi_', 'Graphic Interchange Format', 'system'),
(78, 'ief', 'image/ief', 'Image File - Bitmap graphics', ''),
(79, 'jpeg', 'image/jpeg image/jpg image/jpe_ image/pjpeg image/vnd.swiftview-jpeg', 'JPEG/JIFF Image', 'system'),
(80, 'jpg', 'image/jpeg image/jpg image/jp_ application/jpg application/x-jpg image/pjpeg image/pipeg image/vnd.swiftview-jpeg image/x-xbitmap', 'JPEG/JIFF Image', 'system'),
(81, 'jpe', 'image/jpeg', 'JPEG/JIFF Image', 'system'),
(82, 'png', 'image/png application/png application/x-png', 'Portable (Public) Network Graphic', 'system'),
(83, 'tiff', 'image/tiff', 'Tagged Image Format File', 'system'),
(84, 'tif', 'image/tif image/x-tif image/tiff image/x-tiff application/tif application/x-tif application/tiff application/x-tiff', 'Tagged Image Format File', 'system'),
(85, 'ico', 'image/ico image/x-icon application/ico application/x-ico application/x-win-bitmap image/x-win-bitmap application/octet-stream', 'Windows Icon', ''),
(86, 'wbmp', 'image/vnd.wap.wbmp', 'Wireless Bitmap File Format', ''),
(87, 'ras', 'application/ras application/x-ras image/ras', 'Sun Raster Graphic', ''),
(88, 'pnm', 'image/x-portable-anymap', 'PBM Portable Any Map Graphic Bitmap', ''),
(89, 'pbm', 'image/portable bitmap image/x-portable-bitmap image/pbm image/x-pbm', 'UNIX Portable Bitmap Graphic', ''),
(90, 'pgm', 'image/x-portable-graymap image/x-pgm', 'Portable Graymap Graphic', ''),
(91, 'ppm', 'image/x-portable-pixmap application/ppm application/x-ppm image/x-p image/x-ppm', 'PBM Portable Pixelmap Graphic', ''),
(92, 'rgb', 'image/rgb image/x-rgb', 'Silicon Graphics RGB Bitmap', ''),
(93, 'xbm', 'image/x-xpixmap image/x-xbitmap image/xpm image/x-xpm', 'X Bitmap Graphic', ''),
(94, 'xpm', 'image/x-xpixmap', 'BMC Software Patrol UNIX Icon File', ''),
(95, 'xwd', 'image/x-xwindowdump image/xwd image/x-xwd application/xwd application/x-xwd', 'X Windows Dump', ''),
(96, 'igs', 'model/iges application/iges application/x-iges application/igs application/x-igs drawing/x-igs image/x-igs', 'Initial Graphics Exchange Specification Format', ''),
(97, 'css', 'application/css-stylesheet text/css', 'Hypertext Cascading Style Sheet', ''),
(98, 'html', 'text/html text/plain', 'Hypertext Markup Language', ''),
(99, 'htm', 'text/html', 'Hypertext Markup Language', ''),
(100, 'txt', 'text/plain application/txt browser/internal', 'Text File', 'system'),
(101, 'rtf', 'application/rtf application/x-rtf text/rtf text/richtext application/msword application/doc application/x-soffice', 'Rich Text Format File', 'system'),
(102, 'wml', 'text/vnd.wap.wml text/wml', 'Website META Language File', ''),
(103, 'wmls', 'text/vnd.wap.wmlscript', 'WML Script', ''),
(104, 'etx', 'text/x-setext', 'SetText Structure Enhanced Text', ''),
(105, 'xml', 'text/xml application/xml application/x-xml', 'Extensible Markup Language File', ''),
(106, 'xsl', 'text/xml', 'XML Stylesheet', ''),
(107, 'php', 'text/php application/x-httpd-php application/php magnus-internal/shellcgi application/x-php', 'PHP Script', ''),
(108, 'php3', 'text/php3 application/x-httpd-php', 'PHP Script', ''),
(109, 'mpeg', 'video/mpeg', 'MPEG Movie', ''),
(110, 'mpg', 'video/mpeg video/mpg video/x-mpg video/mpeg2 application/x-pn-mpg video/x-mpeg video/x-mpeg2a audio/mpeg audio/x-mpeg image/mpg', 'MPEG 1 System Stream', ''),
(111, 'mpe', 'video/mpeg', 'MPEG Movie Clip', ''),
(112, 'qt', 'video/quicktime audio/aiff audio/x-wav video/flc', 'QuickTime Movie', ''),
(113, 'mov', 'video/quicktime video/x-quicktime image/mov audio/aiff audio/x-midi audio/x-wav video/avi', 'QuickTime Video Clip', ''),
(114, 'avi', 'video/avi video/msvideo video/x-msvideo image/avi video/xmpg2 application/x-troff-msvideo audio/aiff audio/avi', 'Audio Video Interleave File', ''),
(115, 'movie', 'video/sgi-movie video/x-sgi-movie', 'QuickTime Movie', ''),
(116, 'asf', 'audio/asf application/asx video/x-ms-asf-plugin application/x-mplayer2 video/x-ms-asf application/vnd.ms-asf video/x-ms-asf-plugin video/x-ms-wm video/x-ms-wmx', 'Advanced Streaming Format', ''),
(117, 'asx', 'video/asx application/asx video/x-ms-asf-plugin application/x-mplayer2 video/x-ms-asf application/vnd.ms-asf video/x-ms-asf-plugin video/x-ms-wm video/x-ms-wmx video/x-la-asf', 'Advanced Stream Redirector File', ''),
(118, 'wmv', 'video/x-ms-wmv', 'Windows Media File', ''),
(119, 'wvx', 'video/x-ms-wvx', 'Windows Media Redirector', ''),
(120, 'wm', 'video/x-ms-wm', 'Windows Media A/V File', ''),
(121, 'wmx', 'video/x-ms-wmx', 'Windows Media Player A/V Shortcut', ''),
(122, 'ice', 'x-conference-xcooltalk', 'Cooltalk Audio', ''),
(123, 'rar', 'application/octet-stream', 'WinRAR Compressed Archive', '');

INSERT INTO `group_permission` (`gperm_id`, `gperm_groupid`, `gperm_itemid`, `gperm_modid`, `gperm_name`) VALUES
(NULL, 2, 20, 1, 'use_extension'),
(NULL, 1, 20, 1, 'use_extension'),
(NULL, 2, 19, 1, 'use_extension'),
(NULL, 1, 19, 1, 'use_extension'),
(NULL, 2, 76, 1, 'use_extension'),
(NULL, 1, 76, 1, 'use_extension'),
(NULL, 2, 77, 1, 'use_extension'),
(NULL, 1, 77, 1, 'use_extension'),
(NULL, 2, 82, 1, 'use_extension'),
(NULL, 1, 82, 1, 'use_extension'),
(NULL, 2, 79, 1, 'use_extension'),
(NULL, 1, 79, 1, 'use_extension'),
(NULL, 2, 80, 1, 'use_extension'),
(NULL, 1, 80, 1, 'use_extension'),
(NULL, 2, 81, 1, 'use_extension'),
(NULL, 1, 81, 1, 'use_extension'),
(NULL, 2, 83, 1, 'use_extension'),
(NULL, 1, 83, 1, 'use_extension'),
(NULL, 2, 84, 1, 'use_extension'),
(NULL, 1, 84, 1, 'use_extension'),
(NULL, 2, 100, 1, 'use_extension'),
(NULL, 1, 100, 1, 'use_extension'),
(NULL, 2, 101, 1, 'use_extension'),
(NULL, 1, 101, 1, 'use_extension');
