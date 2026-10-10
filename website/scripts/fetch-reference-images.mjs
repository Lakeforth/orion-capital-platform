// One-off: pulls the home-page and footer artwork from the base44 reference build
// (C:\Users\User\Downloads\figma-untitled-4aa9851b) into public/images so the static
// site never depends on base44's CDN. Raster files are re-encoded to webp; SVGs are
// copied as-is. Safe to re-run — existing files are overwritten.
//
//   node scripts/fetch-reference-images.mjs
import { mkdir, writeFile } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import sharp from 'sharp';

const B = 'https://media.base44.com/images/public/6ab165354490dfcc4aa9851b/';
const OUT = new URL('../public/images/', import.meta.url).pathname.replace(/^\/([A-Za-z]:)/, '$1');

// target path (relative to public/images, no extension for rasters) → source file
const files = {
  // brand
  'brand/favicon-mark':         'c2eb27998_image.png',

  // hero
  'home/hero-slide-1':          '207c61578_generated_94e751f0.png',
  'home/hero-slide-2':          '03259721e_generated_image.png',
  'home/hero-slide-3':          'b8ff753d7_generated_image.png',
  'home/hero-slide-4':          'ba49f67a7_generated_image.png',
  'home/hero-slide-5':          '93e080e2d_generated_image.png',
  'home/icon-eurusd':           'a89432882_a16e57b61_2ac6a5e29486f95681361df1b6c5a00014426c79.png',
  'home/icon-gold':             '1285c7455_d33fc7b14_dd6b26778294957442e2518a3e16939219d3d96a.png',
  'home/icon-tesla':            '01936f79f_d5f6bec28_016040fc016eeb74fe299f83ec208a5321ee1578.png',
  'home/icon-oil':              'd3873e6c7_151cff6e6_0837ce00fda525959bee75dc8f3e4415cef62d0e.png',
  'home/icon-btc':              '948a8b5ee_0fee2f5a1_b83950b02846a5caa240c0c3b54eaa397356d222.png',
  'home/hero-award':            '66a4a87e7_28826d013_a581bdb0e6d8e3ac107714f6ae247172bac56810.png',

  // trading conditions
  'home/conditions-bg':         'f42824992_1e369bc73_f008aab986a0260335d67db0a806b5bc742ff3c5.png',
  'home/tab-1.svg':             'b4af15295_5006bc22b_1_177.svg',
  'home/tab-2.svg':             '38d9b951e_009b92866_1_180.svg',
  'home/tab-3.svg':             '157a13648_aded4dad8_1_183.svg',
  'home/tab-4.svg':             '62cc4496e_8016e67e4_1_186.svg',
  'home/tab-5.svg':             'b4bf21da3_bb874872e_1_189.svg',
  'home/tab-6.svg':             '5052c512e_cf4b2a3bc_1_192.svg',
  'home/tab-7.svg':             'c7690368f_37de5bc39_1_195.svg',
  'home/tab-8.svg':             '231e824e0_784953135_1_198.svg',
  'home/tab-9.svg':             '1f9bf349c_4f41b9c5a_1_201.svg',
  'home/cond-spreads.svg':      '771156079_18c242ca3_1_324.svg',
  'home/cond-leverage.svg':     '93288d0ef_85b070bf6_1_331.svg',
  'home/cond-commission.svg':   '3a4af47eb_def2d5ea0_1_340.svg',
  'home/cond-commission-a.svg': '67c2cb915_89d9dafab_1_339.svg',
  'home/cond-commission-b.svg': '5f08c686a_86a93cca2_1_341.svg',
  'home/cond-execution.svg':    'c71b2d1cc_a21b8e970_1_348.svg',

  // platforms
  'home/blob-1.svg':            'f1ab09825_9fe4ba588_1_358.svg',
  'home/blob-2.svg':            'c7b05cea1_3ec4bb729_1_357.svg',
  'home/blob-3.svg':            '7e17970cb_9295a7770_1_364.svg',
  'home/blob-4.svg':            '11c11e696_7f39a92ce_1_365.svg',
  'home/platform-app':          '87c155bfd_image.png',
  'home/platform-mt':           '5bbebe69e_243da82ae_74f4b9716bff851200689c1abfcda43a63abc299.png',
  'home/platform-options':      '2d7a304ba_9c97d5406_b5266bc0d42523041a0cd296d53f3fb7824063e3.png',
  'home/platform-tradingview':  'f9379378d_c29be890c_7f2033dd959422672ea0ff211896479aa836e789.png',
  'home/platform-cqg':          '13821cb8f_edc75e817_35b1ad27673b200ce8b2109189ce5e9c2a5348e1.png',
  'home/platform-social':       '294b29da5_af31918c7_1d1584599af944023af23500c41d16db5717fadd.png',

  // opportunities
  'home/opp-webtrader':         'e0a923ebb_b4718e04b_edbe482d2efe8e7923a76abbd80e611b156d467c.png',
  'home/opp-mt4':               '484bbd04e_4b9f5d641_9e99f692f669293a7a1c575b694ae24da4b7ff78.png',
  'home/opp-mt5':               'e760a7270_90b60368c_99fb26e973e17756a16fe00610eddd80e3296657.png',
  'home/opp-duplitrade':        '7150c693e_c9d56cab9_2c50098057269cbd1dd6dfd015eab63ae023b01d.png',
  'home/opp-options':           'ab4e6775d_be8a1cbe9_3e6b95d22f226504e57daf0b0ec77f8277644fcd.png',

  // safe hands
  'home/safe-globe':            '68bc42a79_generated_image.png',

  // awards — the reference's seven badges are AvaTrade's own (2025) and are deliberately not fetched

  // trading starts here — four stacked chevron pieces per card
  'home/learn-1a.svg': 'fdcfc5452_1d65e5256_1_783.svg', 'home/learn-1b.svg': '6eb923658_4b0236472_1_782.svg',
  'home/learn-1c.svg': '84870c52d_592d0d4e1_1_781.svg', 'home/learn-1d.svg': '1c5889188_c6525f5ab_1_780.svg',
  'home/learn-2a.svg': '29ab7e6f9_05bcd04c3_1_801.svg', 'home/learn-2b.svg': '9758e92ff_ba46790df_1_800.svg',
  'home/learn-2c.svg': '1ab987c94_1d7460353_1_799.svg', 'home/learn-2d.svg': '15cfdf8ec_2245baffb_1_798.svg',
  'home/learn-3a.svg': 'b7c61f923_51c7474d6_1_819.svg', 'home/learn-3b.svg': 'deaf38960_64e24228a_1_818.svg',
  'home/learn-3c.svg': '060778594_d2f55b394_1_817.svg', 'home/learn-3d.svg': '6e611a819_30624d76c_1_816.svg',

  // features + partner
  'home/features-bg':           '115f717bb_generated_image.png',
  'home/partner-check-bg.svg':  'd7e4088e2_dffb5253c_1_887.svg',
  'home/partner-check.svg':     'd42ab66ec_ac7f10cdb_1_888.svg',
  'home/partner-dashboard':     '824cd3e84_image.png',

  // footer
  'home/payments-strip':        'c2ae62dc4_55f9f9947_d9695601dee1aa90a31a115c6428f3038acf3047.png',
};

let ok = 0;
for (const [target, src] of Object.entries(files)) {
  const res = await fetch(B + src);
  if (!res.ok) { console.error(`FAIL ${res.status} ${src}`); continue; }
  const buf = Buffer.from(await res.arrayBuffer());
  const isSvg = target.endsWith('.svg');
  const out = join(OUT, isSvg ? target : `${target}.webp`);
  await mkdir(dirname(out), { recursive: true });
  if (isSvg) await writeFile(out, buf);
  else await sharp(buf).webp({ quality: 82 }).toFile(out);
  const meta = isSvg ? '' : await sharp(out).metadata().then((m) => ` ${m.width}x${m.height}`);
  console.log(`ok  ${target}${isSvg ? '' : '.webp'}${meta}`);
  ok += 1;
}
console.log(`\n${ok}/${Object.keys(files).length} saved`);
