export const adminCl = (cl: string, params: Record<string, string> = {}): string => {
  const search = new URLSearchParams({ cl, ...params });
  return `/admin/index.php?${search.toString()}`;
};

export const storefrontCl = (cl: string, params: Record<string, string> = {}): string => {
  const search = new URLSearchParams({ cl, ...params });
  return `/index.php?${search.toString()}`;
};

export const URLS = {
  adminLogin: '/admin/',
  adminHome: '/admin/',
  storefrontHome: '/',

  adminRevocationList: adminCl('admin_revocation'),
  adminRevocationConfig: adminCl('revocation_config'),
  adminRevocationDetail: (oxid: string) => adminCl('revocation_main', { oxid }),

  storefrontRevocation: storefrontCl('revocation'),
  storefrontRevocationReceipt: storefrontCl('revocation', { fnc: 'receipt' }),
} as const;
