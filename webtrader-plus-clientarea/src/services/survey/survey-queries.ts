import { useMutation } from "@tanstack/react-query";
import axiosInstance from "@/lib/axios";
import { SurveyRequest } from "./survey-types";

const submitSurvey = async (data: SurveyRequest): Promise<void> => {
  await axiosInstance.post("/leads/survey", data);
};

export const useSurveyMutation = () =>
  useMutation({ mutationFn: submitSurvey });
