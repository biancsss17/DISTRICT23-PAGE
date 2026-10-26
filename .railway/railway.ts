import { defineRailway, github, project, service } from "railway/iac";
// This repository manages only its own resources in the environment. Other
// repositories export their own partial name.
// See https://docs.railway.com/infrastructure-as-code#multi-repo-projects
export const partial = "fysd23website";
export default defineRailway(() => {
  const fysd23website = service("fysd23website", {
    source: github("biancsss17/fysd23website", { branch: "main", upstreamUrl: "https://github.com/biancsss17/fysd23website" }),
    healthcheck: "/up",
    healthcheckTimeout: 300,
    preDeploy: "php artisan migrate --force",
    replicas: { "iad": 1 },
  });

  return project("upbeat-love", {
    variables: { managed: false },
    resources: [fysd23website],
  });
});
