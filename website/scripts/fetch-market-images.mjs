// One-off: pulls the market-page artwork (Forex, Crypto, Stocks, Indices and their guides)
// from the base44 reference build into public/images/markets. Same rules as
// fetch-reference-images.mjs: rasters → webp (capped at 1920px wide), SVGs copied as-is.
//
//   node scripts/fetch-market-images.mjs            (everything)
//   node scripts/fetch-market-images.mjs etfs commodities   (only targets under those folders)
import { mkdir, writeFile } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import sharp from 'sharp';

const B = 'https://media.base44.com/images/public/6ab165354490dfcc4aa9851b/';
const OUT = new URL('../public/images/markets/', import.meta.url).pathname.replace(/^\/([A-Za-z]:)/, '$1');

const files = {
  // ── shared icons (taken once from the Forex page / guide; every market reuses them)
  'shared/stat-est-a.svg': 'f2968b1d1_ae70444d9_29_1384.svg',
  'shared/stat-est-b.svg': 'dbd3c693d_a2a0b4774_29_1382.svg',
  'shared/stat-est-c.svg': 'eae86a401_4524e4ada_29_1383.svg',
  'shared/stat-users-a.svg': 'f0eb9de7b_f9e8296ef_29_1394.svg',
  'shared/stat-users-b.svg': 'ff128d84e_e2de9a3f7_29_1393.svg',
  'shared/stat-users-c.svg': '7cf213495_1dc2d37e6_29_1396.svg',
  'shared/stat-users-d.svg': 'e9d2fc9c7_bdd2bbd5d_29_1395.svg',
  'shared/stat-markets-a.svg': '7be2767d1_6f3ac936f_29_1402.svg',
  'shared/stat-markets-b.svg': '66e51b98b_b19c6c0f0_29_1403.svg',
  'shared/cond-spreads.svg': '94352a873_869a92cc6_24_283.svg',
  'shared/cond-leverage.svg': '51bf679f4_429337395_24_290.svg',
  'shared/cond-comm.svg': 'a9ef352f6_f38b4996f_24_299.svg',
  'shared/cond-comm-a.svg': 'a716e2217_20c6e77c9_24_298.svg',
  'shared/cond-comm-b.svg': '4f4e417c6_ed7ea0e1c_24_300.svg',
  'shared/cond-exec.svg': '30f270f4f_9c1a5a1c5_24_307.svg',
  'shared/check.svg': '1b84f1968_a02f89142_24_478.svg',
  'shared/dark-1.svg': '4f6d27272_53140ecde_24_547.svg',
  'shared/dark-2.svg': 'ef70fa23f_47db802de_24_556.svg',
  'shared/dark-3a.svg': '0dfe393ac_4960c42c1_24_568.svg',
  'shared/dark-3b.svg': '042252786_d54c40540_24_569.svg',
  'shared/dark-4.svg': '08618b2c3_d98900ee4_24_580.svg',
  'shared/step-frame-1.svg': 'a0defc52a_8159529be_24_694.svg',
  'shared/step-frame-2.svg': '60242265a_fc497a249_24_707.svg',
  'shared/step-frame-3.svg': '5284cbbef_b3d84e36a_24_720.svg',
  'shared/step-icon-1.svg': '5cd3cfb82_531d5a913_24_699.svg',
  'shared/step-icon-2.svg': 'c015bf692_4e9d666e4_24_712.svg',
  'shared/step-icon-3.svg': 'd2632c8d8_f6b3877f9_24_725.svg',
  'shared/guide-highlight.svg': 'eef7d86b2_819d1fa56_29_1415.svg',
  'shared/guide-bullet.svg': 'efb72aac2_d8d30f819_29_1422.svg',
  'shared/guide-check.svg': '62ee7ffbf_4c4de0faa_43_1359.svg',
  'shared/play-bg.svg': 'f2d2f3587_0d668bdc9_29_1510.svg',
  'shared/play-icon.svg': '5d072978f_35099d9de_29_1511.svg',

  // ── Forex
  'forex/hero': 'c1d346066_image.png',
  'forex/simplify': 'a6fe352d1_f0ff35b8b_7b42bf84f00c0109b7ce8278479831ee0f91a79c.png',
  'forex/why': '968e9559f_ChatGPT_Image_Sep_24__2026__04_37_02_AM.png',
  'forex/confidence': 'af0bb9016_99481a8da_2a5d0b9c0c287fb8ddfa4fc13ba4d68af4066c72.png',
  'forex/dark-bg': 'cebfec620_1f3e8b331_6755353f18aaab8edcdc23e56c9989d8a9aec33d.png',
  'forex-guide/hero': 'eec4fdc60_5d3e86986_8ad53dc3ae73824c0072c65260c3366d46cc2f49.png',
  'forex-guide/market-map': 'd9531e5e2_08ae28f6b_b4cf1388a6038692a3f971d3c8a5a41a7f0747db.png',
  'forex-guide/how': '2ec16585c_f788be6de_ea358603cae6557eb4d431c0f9c156c6861b6cb1.png',
  'forex-guide/pairs': '20ea8fc6b_6521cc8a9_cfa8055dcf73e6d252e69b3cf5634efc9bafce74.png',
  'forex-guide/terms': '5b1ea0cd1_c809f96ae_6023fa5f510f8cf3f5159512174dbc052190c422.png',
  'forex-guide/participants': '5ac29c305_0b08d4785_788b9b133de76eaf1d73dde878382f04c31a04f0.png',

  // ── Crypto
  'crypto/hero': '93f5efe9d_466540410_ba2baf4b06851043c68a2cd1b7ce9823f6ca1d10.png',
  'crypto/why': 'afe95fb2f_1b87ecea2_5dce2cfd9e4d4cecf043d5f5ae851e748b5512d5.png',
  'crypto/step-into': 'e7e6706e5_637171d60_f69b64032f8ae88b602a6103a40d4f0e732512af.png',
  'crypto/dark-bg': '7aedc11da_07bd8b24c_9aa13d6646bfabac5c92bec8019624e36f04d540.png',
  'crypto-guide/hero': '8bc0d2a39_a8f25b1f8_61fb4fba66636cad8d1c4ab55a23154a4453608d.png',
  'crypto-guide/feature': 'd5f6d7f23_4ca15062f_3b3e6d81bb72f143eabe1cf29937242d32007870.png',
  'crypto-guide/why': '5342aa012_a37b04340_10d8f4df5eea79d954578386034fda66e0f7492b.png',
  'crypto-guide/benefits': 'a16732c70_30b9fea40_356768a0f103ccb3c56eea31907c9a60ec0fbfc0.png',
  'crypto-guide/dark-bg': '7b4d48de2_e5641058d_c0ec5bcccc60dfbe58fe5438615000b7dc87c053.png',

  // ── Stocks
  'stocks/hero': '6ed1fd7a8_5f916968a_a03b6635063d4055e516b4b08fd8703052e1c9d3.png',
  'stocks/edge': '5728013f4_5fdc1883c_c1e577f5094299dc9b5aab13937ade1057e6f983.png',
  'stocks/why': '4f330a7c2_a2983ba10_bc449c88e235f676acef7ec53b25fd7cd9bb2dff.png',
  'stocks/leverage': 'b4e08c0f6_a886b7f91_f37eb28bc915ec30ee19e49c29493eb5799f85db.png',
  'stocks/dark-bg': '37ca09e38_752910be0_129eb12f919a0d13ca803f8ad0bc9f565cebbd9b.png',
  'stocks-guide/hero': '344e864c3_c9ef8158a_d63383883e4110df4b480d768a85e1913757dafa.png',

  // ── Indices
  'indices/hero': '69dce165d_28000cc5-42be-48de-b389-c392f228a3a1.png',
  'indices/edge': '25c5579b3_09db047be_55b6a8746ded9ea6f22e649e77ba7f987f3ddbb8.png',
  'indices/why': '26cd461b9_4327e683d_34d8f8757d549439ea46653ce023aa2354bcd052.png',
  'indices/benefits': '2899b501d_e9669d6ae_de3021c85cd0bd0dd128caa3fdebd0bc955161fa.png',
  'indices/dark-bg': '51694ba97_b03be6a53_327ff3444af7d0b90295bac8c4e904bf43be0980.png',
  'indices-guide/hero': 'ab54bd70f_d86b7e8b8_ec9a3cb8a1377f2e225f972877fdf520b8782f46.png',
  'indices-guide/sectors': '5c28e9d9b_72ba8de3d_e4e24252c6006734ef6a8423b93c9f9f8225d232.png',
  'indices-guide/mcap': '9bf40454f_2df517f04_5e81fd16168d79154917e0e20e2c8a134e7bc3c0.png',
  // ── ETFs
  'etfs/hero': 'c933286d9_93e665643_4e49909adf6d8a7d9732d9052dc2f3521b562593.png',
  'etfs/feature': 'bd1445962_6a74368cb_50352130b51ae1b28538501bb0c94db3bc197408.png',
  'etfs/about': '22ba8df20_4e104c943_6604229cdc659f82ca5d0baebe8e1897364ed6a1.png',
  'etfs/why': '55fb40eca_1dbe4aaf9_eed24e23069227ac2a29fd7af065c9973da6fd82.png',
  'etfs/dark-bg': 'e60aea212_5f232c1e5_66b712299a2dba8616c5fb0e47a6ae15683b6bad.png',
  'etfs-guide/hero': 'b9f6b3ce2_84b870e58_3733064c1a06b38f374967f6a3d08249c2d5085e.png',

  // ── Commodities
  'commodities/hero': '8ef4b15a0_87fd4270f_b5dd16110eead64a2c62a8c96245e4dca8c907c4.png',
  'commodities/master': '8467ce3e7_3d7011433_c9945b74d994429940aeb2f43da011e3afad9dc4.png',
  'commodities/why': '1832f2a6e_ba76d54ae_a6b18a53f68c88225378caac3661412d1816e9d8.png',
  'commodities/opportunities': '7cf6c75c2_27499ffa5_c4698601514d58af5919d2c94246ae66caaf5465.png',
  'commodities/dark-bg': '2aa6e8611_5bc23d9d3_1f9cc6fb5f105ae5691bf4c4d695050d81c739d7.png',
  'commodities-guide/hero': '1676b143e_820b0a2e2_c92d730802061ad4a6d40dc514d7eba36547fcf1.png',
  'commodities-guide/categories': 'd44f00463_14c378508_8d6c7d4a520a740f8c7d64c32e28e024161ca378.png',
  'commodities-guide/factors': 'aaa0bba81_d5de26053_69dd56c4cd2c28fc2de49d3010b3346a6b0c2fdc.png',

// ── Bitcoin
  'btc/hero': '7d9cf4d73_ba47dcf95_da49dc7ef8be3444cd9124ce69abc9e922562f3c.png',
  'btc/feature': 'cac1d6377_bc1145675_99d4f9ce4020c13839d7d3158ad1646d65051ab2.png',
  'btc/why': 'fefd4c8f1_cb2d739de_d6e0b8594089a66f936c4515ab523e39fb12edaa.png',
  'btc/benefits': '6a6ad6571_714cc879f_549ffa76367b381541df5953704ebf11924ff5e4.png',
  'btc/dark-bg': 'f362e5279_e1f50d76d_6019bbebd862d486d7604f06d8cbf53f2ffff385.png',
  'btc-guide/hero': '7c32a5d8a_d998bb963_d308c69d1d0fa1dcd7696345993e01b946130069.png',
  'btc-guide/value': 'ee4f0b9d7_d3eaa4927_60dcb5e7d495f7935b3aaf76b118cd762979082f.png',
  'btc-guide/blockchain': 'a8085b8a3_2f23a69cd_2887df35f64330bd511f20787a961ff00f0ffb1c.png',
  'btc-guide/halving': '5258a69e7_08e49c0d0_9f7e4fa090866329ad9f67189d29b36c6bb580db.png',
  'btc-guide/forks': '7541815a2_4f05a6b48_499709f5094aff2585b946dabca735a1572f3de2.png',
  'btc-guide/uses': '5d8a69b56_07a71bf19_003a18b1460c31abbbc2ed48f5ed03d2b5e9471b.png',

  // ── Gold
  'gold/hero': 'c59e26dd7_28d79220c_b6da5bc5098119598bac4c2301ab6b4dfb01327e.png',
  'gold/feature': '4287e477a_e9f8d9496_f7ac61313ac1bd7aa154c703666ffa380e27a694.png',
  'gold/world': 'f05da82d2_0af7e7ed2_7004b01f2af71e7d4851f1d51d0297c1f42238ef.png',
  'gold/why': '9ab822b0c_76d4db102_a7309fcd54f6eff1a0976b8fc59f354097f04490.png',
  'gold-guide/hero': '533cc8b9e_7445d00e6_870d7fc6f1e5b5c766d91d4c58991e31f44c9ea3.png',
  'gold-guide/factors': 'e77343abb_16c9c957a_559882ea8a23c079e74cd9966aff935efaccc456.png',
  'gold-guide/strategies': '043f28d94_ff27c4d88_cb4f1e5e5fea3785931cdaf1592646f78aafaa3a.png',

  // ── Oil
  'oil/hero': 'faf8db6a2_c08b392d8_0f75e2ba4e6c14284d277d10fe31e521f202ce4f.png',
  'oil/feature': '79bfb32cf_f098ce367_a1629c9e3029cd6d4a37d98719ca1e3f774f89d8.png',
  'oil/world': '5c233323c_4649a6608_7f4a82e8783874bd88a89574e1bb3c857b18077e.png',
  'oil/why': 'e908e74d2_a52262d06_75de835656c80cbbb58bc07040b504fd7ce4bc00.png',
  'oil/dark-bg': 'd36ac3ff2_3869b7ccb_5f6557661a250ad6cc9c8ca7e1b647d52d1026fa.png',
  'oil-guide/hero': '7f2dea9d4_b74383e99_b8fd5065a01350ef5bdf8f3e4ecad85d9309b8bc.png',
  'oil-guide/benchmarks': '28a022c4b_6b994155f_65c583eefbbeafd683bccfd5dfe6db3640ed8140.png',
  'oil-guide/strategy': '31d0c0651_8473038aa_e3f08b451cb02178c98def03a8c9d6adcf47752c.png',
  'oil-guide/history': '38eada0cf_b2cb7b880_737bfed5b4197f538c80ad0388660a9e91956306.png',

  // ── Bonds
  'bonds/hero': 'ba4b439ea_4de5c9b98_96f8fd77332fedfe0e9677659abecb8cd7f6967e.png',
  'bonds/feature': 'de9d3b321_6b3ecf7c8_867bef8475774e004dbc625e2ec860e82be21cb4.png',
  'bonds/about': '964bb7cd6_2d34b5b3e_d2e3668404be6a3cc40bb3e5bec897c61a17e919.png',
  'bonds/why': '20bbc66ea_dfb2eeee9_7552e787ad5cca79c07caf64417744c29e0005d7.png',
  'bonds/dark-bg': '3dc715284_88ed34078_7dc167b8346cae4890b9d2ccf5f3f2bd09122618.png',

  // ── Futures (the reference's Trustpilot-rating and 5-star images are deliberately not fetched)
  'futures/hero': '694347cdf_bdffab08d_e01525b0e888ad75055740a7b7932a68b2dde5fc.png',
  'futures/card-platforms': '024575610_7600a9c26_389067c0aa7a242b49aa050388198bb25b91f2b5.png',
  'futures/card-payments': 'c07c8dbbf_b62a29c3e_ecd26e6a8214ed68071ef9d51d1675724293112d.png',
  'futures/card-learn': '1d80f0d61_fe4a8ee02_860c39cae566afee4661a03dd0bd3b1f2fb765d0.png',
  'futures/card-start': '0817b0d20_716f0928b_00d324a4167d83f94844265f8f91b4d8dced1d17.png',
  'futures/check': 'e408c8937_ee3082c5e_170d0fed03c11c764a6e612dd8a24ac75e313c1b.png',
  'futures/why-work': '97d9aba32_57b3e024a_c32fbb7e98e73ab50f6c0aa4ca0268798db559d0.png',
  'futures/trust': '369f83a41_2a0fdec13_75ac5fa9513102e3a29c217a86073e0c1263455a.png',
  'futures/icon-trophy': '0b52c38aa_0d4ed82ad_18d08752b89fb77c52e303c352569a4bacf07985.png',
  'futures/icon-ribbon': '9bf58a420_e784c864d_f6a236cbe4635d96a35be947afeaeb34e5f251b4.png',
  'futures/icon-shield': '779a1e2ba_356bab5fd_c958b953bb53f798ac69efe4fc890d304753e5fa.png',
  'futures-guide/hero': 'b905d1444_b6c80e919_fe8ec5d4790a69a446b434efc3ca5a7e9abd5f3b.png',
  'futures-guide/diagram': '3637b0da4_c6f1f96b7_d1b46e8a15610580317f1b584ab9e30102ad610d.png',
  'futures-guide/leverage': '47dab16fc_7dc80a9e3_4be7c6026bf1bf93857f14b3c03276a47344e872.png',
  'futures-guide/hedging': '6ac60020b_d1a1a7aef_fb54e15b266638158e2390488c74a67542377020.png',
  'futures-guide/vs-forwards': 'eb1185dee_3b798a8ca_ec377c3ee8a3d68d80144486aac90aaba9533cc6.png',

  'indices-guide/price': '2e40979fc_8af077b46_a3294442d6f6100a3ae153f67f438c0839399818.png',
};

// Deliberately NOT fetched — each carries another firm's branding or claims:
//   crypto/feature        (AvaTrade sign on a building)        → page reuses crypto-guide/feature
//   forex-guide/why       (AvaTrade's regulator list on a map) → page reuses forex/why
//   indices-guide/worldmap (AvaTrade logo as every map pin)    → omitted; the table covers it
//   btc-guide/regulation  (AvaTrade's regulator list on a map) → omitted
//   gold/dark-bg          (empty transparent file)             → page uses gold/hero
//   futures Trustpilot-rating and 5-star images                → not fetched
//
// These infographics carry a small "AVATRADE" wordmark bottom-right on a transparent
// background. clearWatermark() blanks that band: the lowest opaque row within the right
// ~10% of the width, plus the wordmark's height above it.
const watermarked = new Set([
  'forex-guide/pairs', 'forex-guide/participants', 'forex-guide/terms', 'forex-guide/market-map',
  'indices-guide/mcap', 'indices-guide/price', 'indices-guide/sectors',
  'commodities-guide/categories', 'commodities-guide/factors',
  'btc-guide/value', 'btc-guide/blockchain', 'btc-guide/halving', 'btc-guide/forks', 'btc-guide/uses',
  'gold-guide/factors', 'gold-guide/strategies',
  'oil-guide/benchmarks', 'oil-guide/strategy', 'oil-guide/history',
  'futures-guide/diagram', 'futures-guide/leverage',
]);

// Branded UI baked into a screenshot: blend the box away using the colours at its left and
// right edges, row by row. etfs/about shows an "AVA Diamond" logo in the platform header.
const patches = {
  'etfs/about': [132, 26, 217, 49],            // AVA Diamond logo in a platform header
  'gold/why': [369, 49, 410, 66],              // AVA logo on a phone app bar
  'gold-guide/hero': [438, 46, 516, 70],       // AVATRADE wordmark on a phone app bar
  'oil-guide/hero': [433, 46, 511, 70],        // AVATRADE wordmark on a phone app bar
  'futures-guide/hedging': [1700, 530, 1895, 566],     // corner wordmark on an opaque white diagram
  'futures-guide/vs-forwards': [1700, 535, 1895, 571], // corner wordmark on an opaque white diagram
};
async function blendBox(input, [x0, y0, x1, y1]) {
  const { data, info } = await sharp(input).ensureAlpha().raw().toBuffer({ resolveWithObject: true });
  const W = info.width;
  for (let y = y0; y <= y1; y++) {
    const L = (y * W + x0 - 1) * 4, R = (y * W + x1 + 1) * 4;
    for (let x = x0; x <= x1; x++) {
      const t = (x - x0) / (x1 - x0), i = (y * W + x) * 4;
      for (let c = 0; c < 3; c++) data[i + c] = Math.round(data[L + c] * (1 - t) + data[R + c] * t);
    }
  }
  return sharp(data, { raw: { width: W, height: info.height, channels: 4 } }).png().toBuffer();
}

async function clearWatermark(input) {
  const { data, info } = await sharp(input).ensureAlpha().raw().toBuffer({ resolveWithObject: true });
  const { width: W, height: H } = info;
  const xa = Math.floor(W * 0.885), xb = Math.ceil(W * 0.99);
  let y1 = -1;
  for (let y = H - 1; y >= 0 && y1 < 0; y--) {
    for (let x = xa; x < xb; x++) if (data[(y * W + x) * 4 + 3] > 20) { y1 = y; break; }
  }
  if (y1 < 0) return input;
  const ya = Math.max(0, y1 - (Math.round(W * 0.0125) + 4));
  for (let y = ya; y <= Math.min(H - 1, y1 + 2); y++) for (let x = xa; x < xb; x++) data[(y * W + x) * 4 + 3] = 0;
  return sharp(data, { raw: { width: W, height: H, channels: 4 } }).png().toBuffer();
}

sharp.cache(false);
const only = process.argv.slice(2);
const wanted = Object.entries(files).filter(([t]) => !only.length || only.some((o) => t.startsWith(o)));
let ok = 0;
for (const [target, src] of wanted) {
  const res = await fetch(B + src);
  if (!res.ok) { console.error(`FAIL ${res.status} ${target} ← ${src}`); continue; }
  let buf = Buffer.from(await res.arrayBuffer());
  const isSvg = target.endsWith('.svg');
  const out = join(OUT, isSvg ? target : `${target}.webp`);
  await mkdir(dirname(out), { recursive: true });
  if (isSvg) { await writeFile(out, buf); ok += 1; console.log(`ok  ${target}`); continue; }
  buf = await sharp(buf).resize({ width: 1920, withoutEnlargement: true }).png().toBuffer();
  if (watermarked.has(target)) buf = await clearWatermark(buf);
  if (patches[target]) buf = await blendBox(buf, patches[target]);
  await writeFile(out, await sharp(buf).webp({ quality: 82, alphaQuality: 90 }).toBuffer());
  const meta = isSvg ? '' : await sharp(out).metadata().then((m) => ` ${m.width}x${m.height}${m.hasAlpha ? ' alpha' : ''}`);
  console.log(`ok  ${target}${isSvg ? '' : '.webp'}${meta}`);
  ok += 1;
}
console.log(`\n${ok}/${wanted.length} saved`);
